"use client"

import { useEffect, useMemo, useState } from "react"
import { supabase } from "@/lib/supabaseClient"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { TrendingUp, Calendar, BarChart3 } from "lucide-react"

const MONTHS = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"]
const DIVISOR = 1_000_000_000

const CHART = { width: 800, height: 400, padding: 80 }
const innerW = CHART.width - 2 * CHART.padding   // 640
const innerH = CHART.height - 2 * CHART.padding  // 240

export default function TrendRevenueChart({ initialYear = new Date().getFullYear() }) {
  const currentYear = new Date().getFullYear()

  const [selectedYear, setSelectedYear] = useState(Number(initialYear) || currentYear)
  const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth() + 1)
  const [selectedDivision, setSelectedDivision] = useState("All")
  const [source, setSource] = useState("non-ngtma")               

  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)

  const [dgs, setDgs] = useState(Array(12).fill(0))
  const [dps, setDps] = useState(Array(12).fill(0))
  const [dss, setDss] = useState(Array(12).fill(0))

  const clampMonth = (m) => Math.max(1, Math.min(12, m))

  const sumByMonth = (rows, monthKey, revenueKey) => {
    const out = Array(12).fill(0)
    ;(rows ?? []).forEach((r) => {
      const m = Number(r[monthKey] ?? r.month ?? r.MONTH ?? 0)
      const idx = m - 1
      if (idx >= 0 && idx < 12) {
        out[idx] += (r[revenueKey] == null ? 0 : Number(r[revenueKey])) / DIVISOR
      }
    })
    return out
  }

  // fetch data
  useEffect(() => {
    const fetchData = async () => {
      setLoading(true)
      setError(null)
      try {
        if (source === "non-ngtma") {
          // existing behavior: aggregated non-NGTMA view
          const { data, error } = await supabase
            .from("v_monthly_revenue")
            .select("year, month, dgs, dps, dss")
            .eq("year", selectedYear)
            .order("month", { ascending: true })

          if (error) throw error

          const arrDgs = Array(12).fill(0)
          const arrDps = Array(12).fill(0)
          const arrDss = Array(12).fill(0)

          ;(data ?? []).forEach((r) => {
            const idx = (r.month ?? 1) - 1
            if (idx >= 0 && idx < 12) {
              arrDgs[idx] = (r.dgs == null ? 0 : Number(r.dgs)) / DIVISOR
              arrDps[idx] = (r.dps == null ? 0 : Number(r.dps)) / DIVISOR
              arrDss[idx] = (r.dss == null ? 0 : Number(r.dss)) / DIVISOR
            }
          })

          setDgs(arrDgs)
          setDps(arrDps)
          setDss(arrDss)
        } else {
          // NGTMA
          const [dpsRes, dssRes, dgsRes] = await Promise.all([
            supabase.from("v_dps_ngtma").select("YEAR, MONTH, REVENUE_BILL").eq("YEAR", selectedYear),
            supabase.from("v_dss_ngtma").select("YEAR, MONTH, REVENUE_SOLD").eq("YEAR", selectedYear),
            supabase.from("v_dgs_ngtma").select("YEAR, MONTH, REVENUE_SOLD").eq("YEAR", selectedYear),
          ])
          if (dpsRes.error) throw dpsRes.error
          if (dssRes.error) throw dssRes.error
          if (dgsRes.error) throw dgsRes.error

          setDps(sumByMonth(dpsRes.data, "MONTH", "REVENUE_BILL"))
          setDss(sumByMonth(dssRes.data, "MONTH", "REVENUE_SOLD"))
          setDgs(sumByMonth(dgsRes.data, "MONTH", "REVENUE_SOLD"))
        }
      } catch (e) {
        console.error(e)
        setError(e?.message ?? "Failed to load data")
        setDgs(Array(12).fill(0))
        setDps(Array(12).fill(0))
        setDss(Array(12).fill(0))
      } finally {
        setLoading(false)
      }
    }

    fetchData()
  }, [selectedYear, source])

  const md = useMemo(() => {
    const end = clampMonth(selectedMonth)
    return Array.from({ length: end }, (_, i) => ({
      month: MONTHS[i],
      dgs: dgs[i],
      dps: dps[i],
      dss: dss[i],
      total: dgs[i] + dps[i] + dss[i],
    }))
  }, [dgs, dps, dss, selectedMonth])

  const filteredMd = useMemo(() => {
    if (selectedDivision === "All") return md
    return md.map((row) => {
      if (selectedDivision === "DPS") return { ...row, dgs: 0, dss: 0, total: row.dps }
      if (selectedDivision === "DSS") return { ...row, dgs: 0, dps: 0, total: row.dss }
      if (selectedDivision === "DGS") return { ...row, dps: 0, dss: 0, total: row.dgs }
      return row
    })
  }, [md, selectedDivision])

  const maxValue = useMemo(
    () => Math.max(1, ...filteredMd.flatMap((d) => [d.dgs, d.dps, d.dss, d.total])),
    [filteredMd]
  )
  const minValue = useMemo(
    () => Math.min(0, ...filteredMd.flatMap((d) => [d.dgs, d.dps, d.dss])),
    [filteredMd]
  )
  const denom = Math.max(1e-9, maxValue - minValue)

  const xFor = (index, len) => CHART.padding + (index * innerW) / Math.max(1, len - 1)
  const yFor = (value) => CHART.height - CHART.padding - ((value - minValue) / denom) * innerH
  const fmtM = (v) => `${Number(v).toFixed(2)}M`

  const createPath = (data, color, strokeWidth = 4) => {
    const path = data
      .map((v, i) => `${i === 0 ? "M" : "L"} ${xFor(i, data.length)} ${yFor(v)}`)
      .join(" ")
    return (
      <path
        d={path}
        fill="none"
        stroke={color}
        strokeWidth={strokeWidth}
        strokeLinecap="round"
        strokeLinejoin="round"
        filter="drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1))"
      />
    )
  }

  const createGradientArea = (data, gradientId) => {
    const pts = data.map((v, i) => `${xFor(i, data.length)},${yFor(v)}`)
    const pathData = [
      `M ${CHART.padding} ${CHART.height - CHART.padding}`,
      `L ${pts[0] ?? `${CHART.padding},${CHART.height - CHART.padding}`}`,
      ...pts.slice(1).map((p) => `L ${p}`),
      `L ${xFor(Math.max(0, data.length - 1), data.length)} ${CHART.height - CHART.padding}`,
      "Z",
    ].join(" ")
    return <path d={pathData} fill={`url(#${gradientId})`} opacity="0.2" />
  }

  return (
    <Card className="shadow-m border-gray-200 bg-gradient-to-r from-white to-gray-50">
      <CardHeader className="pb-6 border-b border-gray-200">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            <div className="w-12 h-12 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
              <BarChart3 className="h-6 w-6 text-white" />
            </div>
            <div>
              <CardTitle className="text-2xl font-bold text-gray-900 flex items-center gap-3">
                Trend Revenue Analysis
                <TrendingUp className="h-6 w-6 text-green-600" />
              </CardTitle>
              <p className="text-base text-gray-600 mt-1">
                Monthly revenue performance across {selectedDivision === "All" ? "all divisions" : selectedDivision} (YTD {selectedYear})
              </p>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <Calendar className="h-5 w-5 text-gray-500" />
            <span className="text-sm font-medium text-gray-600">
              Jan – {MONTHS[Math.max(1, Math.min(12, selectedMonth)) - 1]} {selectedYear}
            </span>
          </div>
        </div>

        <div className="mt-4 flex items-center gap-4">
          <label className="text-sm text-gray-700 font-medium">Year:</label>
          <select
            value={selectedYear}
            onChange={(e) => setSelectedYear(Number(e.target.value))}
            className="border rounded-lg px-2 py-1 text-sm w-20"
          >
            {Array.from({ length: 6 }, (_, i) => currentYear - i).map((y) => (
              <option key={y} value={y}>{y}</option>
            ))}
          </select>

          <label className="text-sm text-gray-700 font-medium">Month:</label>
          <select
            value={selectedMonth}
            onChange={(e) => setSelectedMonth(Number(e.target.value))}
            className="border rounded-lg px-2 py-1 text-sm w-20"
          >
            {MONTHS.map((m, i) => (
              <option key={m} value={i + 1}>{m}</option>
            ))}
          </select>

          <label className="text-sm text-gray-700 font-medium">Division:</label>
          <select
            value={selectedDivision}
            onChange={(e) => setSelectedDivision(e.target.value)}
            className="border rounded-lg px-2 py-1 text-sm w-28"
          >
            <option value="All">All Division</option>
            <option value="DPS">DPS</option>
            <option value="DSS">DSS</option>
            <option value="DGS">DGS</option>
          </select>

          <label className="text-sm text-gray-700 font-medium">Source:</label>
          <select
            value={source}
            onChange={(e) => setSource(e.target.value)}
            className="border rounded-lg px-2 py-1 text-sm w-32"
          >
            <option value="non-ngtma">Non-NGTMA</option>
            <option value="ngtma">NGTMA</option>
          </select>
        </div>
      </CardHeader>

      <CardContent className="p-8">
        {loading && <div className="text-gray-600">Loading revenue…</div>}
        {error && <div className="text-red-600">Error: {error}</div>}

        {!loading && !error && (
          <>
            {/* KPI cards (unchanged markup/classes) */}
            {selectedDivision === "All" ? (
              <div className="grid grid-cols-3 gap-6 mb-8">
                <div className="bg-sky-100 p-6 rounded-xl border border-sky-400">
                  <div className="flex items-center justify-between mb-3">
                    <h3 className="text-lg font-bold text-sky-700">DPS Performance</h3>
                    <div className="w-4 h-4 bg-sky-700 rounded-full shadow-sm" />
                  </div>
                  <div className="text-3xl font-bold text-sky-700 mb-2">{fmtM(filteredMd.at(-1)?.dps ?? 0)}</div>
                  <div className="text-sm text-sky-700 font-medium">
                    {(() => {
                      const first = filteredMd[0]?.dps ?? 0, last = filteredMd.at(-1)?.dps ?? 0
                      const pct = first > 0 ? ((last - first) / first) * 100 : 0
                      return `${pct >= 0 ? "+" : ""}${pct.toFixed(1)}% MTD Growth`
                    })()}
                  </div>
                </div>

                <div className="bg-blue-100 p-6 rounded-xl border border-blue-400">
                  <div className="flex items-center justify-between mb-3">
                    <h3 className="text-lg font-bold text-blue-900">DSS Performance</h3>
                    <div className="w-4 h-4 bg-blue-900 rounded-full shadow-sm" />
                  </div>
                  <div className="text-3xl font-bold text-blue-900 mb-2">
                    {fmtM(filteredMd.at(-1)?.dss ?? 0)}
                  </div>
                  <div className="text-sm text-blue-900 font-medium">
                    {(() => {
                      const first = filteredMd[0]?.dss ?? 0, last = filteredMd.at(-1)?.dss ?? 0
                      const pct = first > 0 ? ((last - first) / first) * 100 : 0
                      return `${pct >= 0 ? "+" : ""}${pct.toFixed(1)}% MTD Growth`
                    })()}
                  </div>
                </div>

                <div className="bg-amber-100 p-6 rounded-xl border border-amber-400">
                  <div className="flex items-center justify-between mb-3">
                    <h3 className="text-lg font-bold text-amber-800">DGS Performance</h3>
                    <div className="w-4 h-4 bg-amber-500 rounded-full shadow-sm" />
                  </div>
                  <div className="text-3xl font-bold text-amber-700 mb-2">{fmtM(filteredMd.at(-1)?.dgs ?? 0)}</div>
                  <div className="text-sm text-amber-600 font-medium">
                    {(() => {
                      const first = filteredMd[0]?.dgs ?? 0, last = filteredMd.at(-1)?.dgs ?? 0
                      const pct = first > 0 ? ((last - first) / first) * 100 : 0
                      return `${pct >= 0 ? "+" : ""}${pct.toFixed(1)}% MTD Growth`
                    })()}
                  </div>
                </div>
              </div>
            ) : (
              <div className="grid grid-cols-1 gap-6 mb-8">
                {selectedDivision === "DPS" && (
                  <div className="bg-sky-100 p-6 rounded-xl border border-sky-400">
                    <div className="flex items-center justify-between mb-3">
                      <h3 className="text-lg font-bold text-sky-700">DPS Performance</h3>
                      <div className="w-4 h-4 bg-sky-700 rounded-full shadow-sm" />
                    </div>
                    <div className="text-3xl font-bold text-sky-700 mb-2">{fmtM(filteredMd.at(-1)?.dps ?? 0)}</div>
                    <div className="text-sm text-sky-700 font-medium">
                      {(() => {
                        const first = filteredMd[0]?.dps ?? 0, last = filteredMd.at(-1)?.dps ?? 0
                        const pct = first > 0 ? ((last - first) / first) * 100 : 0
                        return `${pct >= 0 ? "+" : ""}${pct.toFixed(1)}% MTD Growth`
                      })()}
                    </div>
                  </div>
                )}
                {selectedDivision === "DSS" && (
                  <div className="bg-blue-100 p-6 rounded-xl border border-blue-400">
                    <div className="flex items-center justify-between mb-3">
                      <h3 className="text-lg font-bold text-blue-900">DSS Performance</h3>
                      <div className="w-4 h-4 bg-blue-900 rounded-full shadow-sm" />
                    </div>
                    <div className="text-3xl font-bold text-blue-900 mb-2">{fmtM(filteredMd.at(-1)?.dss ?? 0)}</div>
                    <div className="text-sm text-blue-900 font-medium">
                      {(() => {
                        const first = filteredMd[0]?.dss ?? 0, last = filteredMd.at(-1)?.dss ?? 0
                        const pct = first > 0 ? ((last - first) / first) * 100 : 0
                        return `${pct >= 0 ? "+" : ""}${pct.toFixed(1)}% MTD Growth`
                      })()}
                    </div>
                  </div>
                )}
                {selectedDivision === "DGS" && (
                  <div className="bg-amber-100 p-6 rounded-xl border border-amber-400">
                    <div className="flex items-center justify-between mb-3">
                      <h3 className="text-lg font-bold text-amber-700">DGS Performance</h3>
                      <div className="w-4 h-4 bg-amber-700 rounded-full shadow-sm" />
                    </div>
                    <div className="text-3xl font-bold text-amber-700 mb-2">{fmtM(filteredMd.at(-1)?.dgs ?? 0)}</div>
                    <div className="text-sm text-amber-700 font-medium">
                      {(() => {
                        const first = filteredMd[0]?.dgs ?? 0, last = filteredMd.at(-1)?.dgs ?? 0
                        const pct = first > 0 ? ((last - first) / first) * 100 : 0
                        return `${pct >= 0 ? "+" : ""}${pct.toFixed(1)}% MTD Growth`
                      })()}
                    </div>
                  </div>
                )}
              </div>
            )}

            {/* Chart (unchanged styling) */}
            <div className="relative bg-gradient-to-br from-gray-50 via-white to-blue-50 p-8 rounded-2xl border border-gray-200 shadow-inner">
              <svg width="100%" height="500" viewBox={`0 0 ${CHART.width} 500`} className="overflow-visible">
                <defs>
                  <linearGradient id="dpsGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stopColor="#0070c0" stopOpacity="0.3" />
                    <stop offset="100%" stopColor="#0070c0" stopOpacity="0.05" />
                  </linearGradient>
                  <linearGradient id="dssGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stopColor="#203764" stopOpacity="0.3" />
                    <stop offset="100%" stopColor="#203764" stopOpacity="0.05" />
                  </linearGradient>
                  <linearGradient id="dgsGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stopColor="#bf8f00" stopOpacity="0.3" />
                    <stop offset="100%" stopColor="#bf8f00" stopOpacity="0.05" />
                  </linearGradient>
                </defs>

                {[0, 20, 40, 60, 80, 100].map((percent) => {
                  const y = CHART.height - CHART.padding - (percent / 100) * innerH
                  const val = Math.round(minValue + (percent / 100) * (maxValue - minValue))
                  return (
                    <g key={percent}>
                      <line x1={CHART.padding} y1={y} x2={CHART.width - CHART.padding} y2={y} stroke="#e5e7eb" strokeWidth="1" strokeDasharray="4,4" />
                      <text x={CHART.padding - 10} y={y + 5} fontSize="14" fill="#6b7280" textAnchor="end" fontWeight="500">
                        {val}M
                      </text>
                    </g>
                  )
                })}

                {filteredMd.map((item, index) => {
                  const x = xFor(index, filteredMd.length)
                  return (
                    <g key={item.month}>
                      <text x={x} y="460" fontSize="16" fill="#374151" textAnchor="middle" fontWeight="600">
                        {item.month}
                      </text>
                      <line x1={x} y1={CHART.height - CHART.padding} x2={x} y2={CHART.height - CHART.padding + 10} stroke="#9ca3af" strokeWidth="2" />
                    </g>
                  )
                })}

                {selectedDivision === "All" && (
                  <>
                    {createGradientArea(filteredMd.map((d) => d.dps), "dpsGradient")}
                    {createGradientArea(filteredMd.map((d) => d.dss), "dssGradient")}
                    {createGradientArea(filteredMd.map((d) => d.dgs), "dgsGradient")}
                  </>
                )}
                {selectedDivision === "DPS" && createGradientArea(filteredMd.map((d) => d.dps), "dpsGradient")}
                {selectedDivision === "DSS" && createGradientArea(filteredMd.map((d) => d.dss), "dssGradient")}
                {selectedDivision === "DGS" && createGradientArea(filteredMd.map((d) => d.dgs), "dgsGradient")}

                {selectedDivision === "All" && (
                  <>
                    {createPath(filteredMd.map((d) => d.dps), "#0070c0", 4)}
                    {createPath(filteredMd.map((d) => d.dss), "#203764", 4)}
                    {createPath(filteredMd.map((d) => d.dgs), "#bf8f00", 4)}
                  </>
                )}
                {selectedDivision === "DPS" && createPath(filteredMd.map((d) => d.dps), "#0070c0", 4)}
                {selectedDivision === "DSS" && createPath(filteredMd.map((d) => d.dss), "#203764", 4)}
                {selectedDivision === "DGS" && createPath(filteredMd.map((d) => d.dgs), "#bf8f00", 4)}

                {filteredMd.map((item, index) => {
                  const x = xFor(index, filteredMd.length)
                  return (
                    <g key={item.month}>
                      {selectedDivision === "All" && (
                        <>
                          <circle cx={x} cy={yFor(item.dps)} r="8" fill="#0070c0" stroke="white" strokeWidth="3" />
                          <circle cx={x} cy={yFor(item.dps)} r="4" fill="white" />
                          <text x={x} y={yFor(item.dps) - 20} fontSize="12" fill="#0070c0" textAnchor="middle" fontWeight="bold">
                            {fmtM(item.dps)}
                          </text>

                          <circle cx={x} cy={yFor(item.dss)} r="8" fill="#203764" stroke="white" strokeWidth="3" />
                          <circle cx={x} cy={yFor(item.dss)} r="4" fill="white" />
                          <text x={x} y={yFor(item.dss) - 20} fontSize="12" fill="#203764" textAnchor="middle" fontWeight="bold">
                            {fmtM(item.dss)}
                          </text>

                          <circle cx={x} cy={yFor(item.dgs)} r="8" fill="#bf8f00" stroke="white" strokeWidth="3" />
                          <circle cx={x} cy={yFor(item.dgs)} r="4" fill="white" />
                          <text x={x} y={yFor(item.dgs) - 20} fontSize="12" fill="#bf8f00" textAnchor="middle" fontWeight="bold">
                            {fmtM(item.dgs)}
                          </text>
                        </>
                      )}
                      {selectedDivision === "DPS" && (
                        <>
                          <circle cx={x} cy={yFor(item.dps)} r="8" fill="#0070c0" stroke="white" strokeWidth="3" />
                          <circle cx={x} cy={yFor(item.dps)} r="4" fill="white" />
                          <text x={x} y={yFor(item.dps) - 20} fontSize="12" fill="#0070c0" textAnchor="middle" fontWeight="bold">
                            {fmtM(item.dps)}
                          </text>
                        </>
                      )}
                      {selectedDivision === "DSS" && (
                        <>
                          <circle cx={x} cy={yFor(item.dss)} r="8" fill="#203764" stroke="white" strokeWidth="3" />
                          <circle cx={x} cy={yFor(item.dss)} r="4" fill="white" />
                          <text x={x} y={yFor(item.dss) - 20} fontSize="12" fill="#203764" textAnchor="middle" fontWeight="bold">
                            {fmtM(item.dss)}
                          </text>
                        </>
                      )}
                      {selectedDivision === "DGS" && (
                        <>
                          <circle cx={x} cy={yFor(item.dgs)} r="8" fill="#bf8f00" stroke="white" strokeWidth="3" />
                          <circle cx={x} cy={yFor(item.dgs)} r="4" fill="white" />
                          <text x={x} y={yFor(item.dgs) - 20} fontSize="12" fill="#bf8f00" textAnchor="middle" fontWeight="bold">
                            {fmtM(item.dgs)}
                          </text>
                        </>
                      )}
                    </g>
                  )
                })}
              </svg>
            </div>

            {/* Legend (unchanged) */}
            {selectedDivision === "All" && (
              <div className="flex justify-center gap-12 mt-8">
                <div className="flex items-center gap-3 p-4 bg-sky-100 rounded-xl border border-sky-400">
                  <div className="w-6 h-6 bg-sky-700 rounded-full shadow-sm" />
                  <div>
                    <span className="text-base font-bold text-sky-700">DPS (Private)</span>
                    <div className="text-sm text-sky-700">Latest: {fmtM(filteredMd.at(-1)?.dps ?? 0)}</div>
                  </div>
                </div>
                <div className="flex items-center gap-3 p-4 bg-blue-100 rounded-xl border border-blue-400">
                  <div className="w-6 h-6 bg-blue-900 rounded-full shadow-sm" />
                  <div>
                    <span className="text-base font-bold text-blue-900">DSS (BUMN/Korporasi)</span>
                    <div className="text-sm text-blue-900">Latest: {fmtM(filteredMd.at(-1)?.dss ?? 0)}</div>
                  </div>
                </div>
                <div className="flex items-center gap-3 p-4 bg-amber-100 rounded-xl border border-amber-400">
                  <div className="w-6 h-6 bg-amber-700 rounded-full shadow-sm" />
                  <div>
                    <span className="text-base font-bold text-amber-700">DGS (Government)</span>
                    <div className="text-sm text-amber-700">Latest: {fmtM(filteredMd.at(-1)?.dgs ?? 0)}</div>
                  </div>
                </div>
              </div>
            )}
            {selectedDivision === "DPS" && (
              <div className="flex justify-center gap-12 mt-8">
                <div className="flex items-center gap-3 p-4 bg-blue-100 rounded-xl border border-sky-400">
                  <div className="w-6 h-6 bg-sky-700 rounded-full shadow-sm" />
                  <div>
                    <span className="text-base font-bold text-sky-700">DPS (Private)</span>
                    <div className="text-sm text-sky-700">Latest: {fmtM(filteredMd.at(-1)?.dps ?? 0)}</div>
                  </div>
                </div>
              </div>
            )}
            {selectedDivision === "DSS" && (
              <div className="flex justify-center gap-12 mt-8">
                <div className="flex items-center gap-3 p-4 bg-blue-100 rounded-xl border border-blue-400">
                  <div className="w-6 h-6 bg-blue-900 rounded-full shadow-sm" />
                  <div>
                    <span className="text-base font-bold text-blue-900">DSS (BUMN/Korporasi)</span>
                    <div className="text-sm text-blue-900">Latest: {fmtM(filteredMd.at(-1)?.dss ?? 0)}</div>
                  </div>
                </div>
              </div>
            )}
            {selectedDivision === "DGS" && (
              <div className="flex justify-center gap-12 mt-8">
                <div className="flex items-center gap-3 p-4 bg-amber-100 rounded-xl border border-amber-400">
                  <div className="w-6 h-6 bg-amber-700 rounded-full shadow-sm" />
                  <div>
                    <span className="text-base font-bold text-amber-700">DGS (Government)</span>
                    <div className="text-sm text-amber-700">Latest: {fmtM(filteredMd.at(-1)?.dgs ?? 0)}</div>
                  </div>
                </div>
              </div>
            )}
          </>
        )}
      </CardContent>
    </Card>
  )
}
