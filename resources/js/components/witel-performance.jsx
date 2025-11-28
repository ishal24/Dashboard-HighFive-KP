"use client";

import "../../../public/css/inertia.css";
import React, { useEffect, useMemo, useRef, useState } from "react";
import { supabase } from "@/lib/supabaseClient";
import { formatIDRCompact } from "@/components/ui/formatIDRCompact";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Progress } from "@/components/ui/progress";
import { Badge } from "@/components/ui/badge";

const DIVISOR_TO_M = 1_000_000;
const toNum = (x) => (x == null ? 0 : typeof x === "string" ? parseFloat(x) : Number(x));

const statusDot = (ach) => {
  if (ach == null) return "bg-gray-400";
  if (ach >= 100) return "bg-green-600";
  if (ach >= 80) return "bg-amber-600";
  if (ach >= 0) return "bg-red-600";
  return "bg-red-500";
};

const badgeVariant = (ach) => {
  if (ach == null) return "secondary";
  if (ach >= 100) return "default";
  if (ach >= 80) return "secondary";
  return "destructive";
};

export function WitelPerformance() {
  const now = new Date();
  const currentYear = now.getFullYear();

  const [year, setYear] = useState(currentYear);
  const [month, setMonth] = useState(now.getMonth() + 1);
  const [source, setSource] = useState("non-ngtma");

  const [rows, setRows] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // Top-10 expansion state
  const [expandedWitel, setExpandedWitel] = useState(null);
  const [top10, setTop10] = useState([]);
  const [top10Loading, setTop10Loading] = useState(false);
  const top10ReqIdRef = useRef(0);

  // caches
  const targetsCacheRef = useRef(null);
  const revenueNonRef = useRef({});
  const revenueNgtmaRef = useRef({});

  // network guards
  const reqIdRef = useRef(0);
  const abortRef = useRef(null);

  const dataRefForSource = source === "ngtma" ? revenueNgtmaRef : revenueNonRef;

  const buildDisplays = (y, m) => {
    const tMap = targetsCacheRef.current ?? new Map();
    const monthlyMap = dataRefForSource.current[y] ?? new Map();

    const allWitels = new Set([...tMap.keys(), ...monthlyMap.keys()]);
    const out = [];

    for (const witel of allWitels) {
      const monthsArr = monthlyMap.get(witel) ?? Array(12).fill(0);
      const revenueM = monthsArr.slice(0, Math.max(1, Math.min(12, m))).reduce((s, v) => s + v, 0);
      const targetM = tMap.get(witel) ?? 0;
      const achievement = targetM > 0 ? (revenueM / targetM) * 100 : (targetM === 0 ? null : 0);
      out.push({ name: witel, revenueM, targetM, achievement });
    }

    out.sort((a, b) => {
      const aAch = a.achievement ?? -1;
      const bAch = b.achievement ?? -1;
      if (bAch !== aAch) return bAch - aAch;
      return b.revenueM - a.revenueM;
    });

    return out;
  };

  // Prefetch target
  const prefetchTargets = async () => {
    if (targetsCacheRef.current) return;
    const targ = await supabase.from("v_monthly_witel_target").select("witel,total_target");
    if (targ.error) throw targ.error;

    const tMap = new Map();
    (targ.data ?? []).forEach((r) => {
      tMap.set(r.witel, toNum(r.total_target) / DIVISOR_TO_M);
    });
    targetsCacheRef.current = tMap;
  };

  //Prefetch Non NGTMA revenue
  const prefetchYearNon = async (y) => {
    if (revenueNonRef.current[y]) return;

    const rev = await supabase
      .from("v_monthly_witel_revenue")
      .select("year,month,witel,total_revenue")
      .eq("year", y);

    if (rev.error) throw rev.error;

    const monthlyMap = new Map();
    (rev.data ?? []).forEach((r) => {
      if (!r.witel) return;
      const idx = Math.max(0, Math.min(11, (r.month ?? 1) - 1));
      const valRp = r.total != null ? toNum(r.total) : toNum(r.total_revenue);
      const arr = monthlyMap.get(r.witel) ?? Array(12).fill(0);
      arr[idx] += valRp / DIVISOR_TO_M;
      monthlyMap.set(r.witel, arr);
    });

    revenueNonRef.current[y] = monthlyMap;
  };

  const prefetchYearNgtma = async (y) => {
    if (revenueNgtmaRef.current[y]) return;

    const [dpsRes, dssRes, dgsRes] = await Promise.all([
      supabase.from("v_dps_ngtma").select("YEAR,MONTH,WITEL_HO,STANDARD_NAME,REVENUE_BILL").eq("YEAR", y),
      supabase.from("v_dss_ngtma").select("YEAR,MONTH,WITEL_HO,STANDARD_NAME,REVENUE_SOLD").eq("YEAR", y),
      supabase.from("v_dgs_ngtma").select("YEAR,MONTH,WITEL_HO,STANDARD_NAME,REVENUE_SOLD").eq("YEAR", y),
    ]);
    if (dpsRes.error) throw dpsRes.error;
    if (dssRes.error) throw dssRes.error;
    if (dgsRes.error) throw dgsRes.error;

    const monthlyMap = new Map();
    const addVal = (witel, mIdx, val) => {
      if (!witel) return;
      const arr = monthlyMap.get(witel) ?? Array(12).fill(0);
      arr[mIdx] += val / DIVISOR_TO_M;
      monthlyMap.set(witel, arr);
    };

    for (const r of dpsRes.data ?? []) {
      const idx = Math.max(0, Math.min(11, toNum(r.MONTH) - 1));
      addVal(r.WITEL_HO ?? r.WITEL_BILL, idx, toNum(r.REVENUE_BILL));
    }
    for (const r of dssRes.data ?? []) {
      const idx = Math.max(0, Math.min(11, toNum(r.MONTH) - 1));
      addVal(r.WITEL_HO ?? r.WITEL_BILL, idx, toNum(r.REVENUE_SOLD));
    }
    for (const r of dgsRes.data ?? []) {
      const idx = Math.max(0, Math.min(11, toNum(r.MONTH) - 1));
      addVal(r.WITEL_HO ?? r.WITEL_BILL, idx, toNum(r.REVENUE_SOLD));
    }

    revenueNgtmaRef.current[y] = monthlyMap;
  };

  const prefetchYear = async (y) => {
    const myReqId = ++reqIdRef.current;
    setError(null);

    const hasTargets = !!targetsCacheRef.current;
    const hasYear =
      source === "ngtma"
        ? !!revenueNgtmaRef.current[y]
        : !!revenueNonRef.current[y];

    if (hasYear && hasTargets) {
      if (myReqId === reqIdRef.current) {
        setRows(buildDisplays(y, month));
        setLoading(false);
      }
      return;
    }

    setLoading(true);
    try {
      abortRef.current?.abort();
      abortRef.current = new AbortController();

      if (!targetsCacheRef.current) {
        await prefetchTargets();
      }

      if (source === "ngtma") {
        if (!revenueNgtmaRef.current[y]) await prefetchYearNgtma(y);
      } else {
        if (!revenueNonRef.current[y]) await prefetchYearNon(y);
      }

      if (myReqId === reqIdRef.current) {
        setRows(buildDisplays(y, month));
      }
    } catch (e) {
      if (myReqId === reqIdRef.current) {
        setError(e?.message ?? "Failed to prefetch WITEL data");
        setRows([]);
      }
    } finally {
      if (myReqId === reqIdRef.current) setLoading(false);
    }
  };

  // Top 10 (Non-NGTMA: existing view)
  const fetchTop10Non = async (witel) => {
    const { data, error } = await supabase
      .from("v_monthly_witel_standard_revenue")
      .select("standard_name,total_revenue")
      .eq("year", year)
      .eq("month", month)
      .eq("witel", witel)
      .order("total_revenue", { ascending: false })
      .limit(10);
    if (error) throw error;
    return data ?? [];
  };

  // Top 10
  const fetchTop10Ngtma = async (witel) => {
    const [dps, dss, dgs] = await Promise.all([
      supabase
        .from("v_dps_ngtma")
        .select("STANDARD_NAME,REVENUE_BILL")
        .eq("YEAR", year)
        .eq("MONTH", month)
        .eq("WITEL_HO", witel),
      supabase
        .from("v_dss_ngtma")
        .select("STANDARD_NAME,REVENUE_SOLD")
        .eq("YEAR", year)
        .eq("MONTH", month)
        .eq("WITEL_HO", witel),
      supabase
        .from("v_dgs_ngtma")
        .select("STANDARD_NAME,REVENUE_SOLD")
        .eq("YEAR", year)
        .eq("MONTH", month)
        .eq("WITEL_HO", witel),
    ]);
    if (dps.error) throw dps.error;
    if (dss.error) throw dss.error;
    if (dgs.error) throw dgs.error;

    const sumByStd = new Map(); // standard_name -> total_revenue (Rp)
    const add = (name, v) => sumByStd.set(name, (sumByStd.get(name) ?? 0) + toNum(v));

    (dps.data ?? []).forEach((r) => add(r.STANDARD_NAME, r.REVENUE_BILL));
    (dss.data ?? []).forEach((r) => add(r.STANDARD_NAME, r.REVENUE_SOLD));
    (dgs.data ?? []).forEach((r) => add(r.STANDARD_NAME, r.REVENUE_SOLD));

    const rows = [...sumByStd.entries()]
      .map(([standard_name, total_revenue]) => ({ standard_name, total_revenue }))
      .sort((a, b) => toNum(b.total_revenue) - toNum(a.total_revenue))
      .slice(0, 10);

    return rows;
  };

  // Fetch Top 10
  const fetchTop10 = async (witel) => {
    const myId = ++top10ReqIdRef.current;
    setTop10Loading(true);
    setExpandedWitel(witel);
    try {
      const data =
        source === "ngtma" ? await fetchTop10Ngtma(witel) : await fetchTop10Non(witel);
      if (myId === top10ReqIdRef.current) setTop10(data);
    } catch (err) {
      if (myId === top10ReqIdRef.current) setTop10([]);
      console.error("Top10 fetch error", err);
    } finally {
      if (myId === top10ReqIdRef.current) setTop10Loading(false);
    }
  };

  useEffect(() => {
    // Prefetch current year
    const run = async () => {
      setLoading(true);
      setError(null);
      try {
        await prefetchTargets();
        await Promise.all([prefetchYearNon(currentYear), prefetchYearNgtma(currentYear)]);
        setRows(buildDisplays(year, month));
      } catch (e) {
        setError(e?.message ?? "Failed to prefetch WITEL data");
        setRows([]);
      } finally {
        setLoading(false);
      }
    };
    run();

    const onHide = () => abortRef.current?.abort();
    const onShow = () => {
      const store = dataRefForSource.current;
      if (!targetsCacheRef.current || !store[year]) prefetchYear(year);
    };
    const handler = () => (document.hidden ? onHide() : onShow());
    document.addEventListener("visibilitychange", handler);
    return () => {
      document.removeEventListener("visibilitychange", handler);
      abortRef.current?.abort();
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    if (dataRefForSource.current[year] && targetsCacheRef.current) {
      setRows(buildDisplays(year, month));
      setLoading(false);
      setError(null);
    } else {
      prefetchYear(year);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [year, source]);

  useEffect(() => {
    if (targetsCacheRef.current && dataRefForSource.current[year]) {
      setRows(buildDisplays(year, month));
    }
  }, [month]);

  useEffect(() => {
    if (expandedWitel) fetchTop10(expandedWitel);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [year, month, source, expandedWitel]);

  const totalRevenueM = useMemo(
    () => (rows ?? []).reduce((s, r) => s + r.revenueM, 0),
    [rows]
  );

  return (
    <Card className="shadow-sm border-gray-200">
      <CardHeader className="pb-4">
        <div className="flex items-center justify-between">
          <CardTitle className="text-lg font-bold text-gray-900">
            Performance Witel – {year}
          </CardTitle>

          <div className="mt-4 flex items-center gap-4">
            <div className="flex items-center gap-2">
              <label htmlFor="year" className="text-sm text-gray-600">Year:</label>
              <select
                id="year"
                className="border rounded-lg px-2 py-1 text-sm w-20"
                value={year}
                onChange={(e) => setYear(Number(e.target.value))}
              >
                {[currentYear - 2, currentYear - 1, currentYear, currentYear + 1, currentYear + 2].map((y) => (
                  <option key={y} value={y}>{y}</option>
                ))}
              </select>
            </div>

            <div className="flex items-center gap-2">
              <label htmlFor="month" className="text-sm text-gray-700 font-medium">Month:</label>
              <select
                id="month"
                className="border rounded-lg px-2 py-1 text-sm w-20"
                value={month}
                onChange={(e) => setMonth(Number(e.target.value))}
              >
                {["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Des"].map((m, i) => (
                  <option key={m} value={i + 1}>{m}</option>
                ))}
              </select>
            </div>

            {/* NEW: Source selector (keeps same layout/classes) */}
            <div className="flex items-center gap-2">
              <label htmlFor="source" className="text-sm text-gray-700 font-medium">Source:</label>
              <select
                id="source"
                className="border rounded-lg px-2 py-1 text-sm w-32"
                value={source}
                onChange={(e) => setSource(e.target.value)}
              >
                <option value="non-ngtma">Non-NGTMA</option>
                <option value="ngtma">NGTMA</option>
              </select>
            </div>
          </div>
        </div>

        <div className="mt-1 text-sm text-gray-600">
          Total Revenue s/d {month}/{year}:{" "}
          <span className="font-semibold text-gray-900">
            {formatIDRCompact(totalRevenueM * 1_000_000)}
          </span>
        </div>
      </CardHeader>

      <CardContent>
        {loading && !dataRefForSource.current[year] && (
          <div className="text-sm text-gray-600">Loading…</div>
        )}
        {error && <div className="text-sm text-red-600">Error: {error}</div>}

        {!error && rows && (
          <div className="space-y-4">
            {rows.map((w) => {
              const ach = w.achievement;
              const badgeTxt = ach == null ? "—" : `${ach.toFixed(1)}%`;
              const progressVal = ach == null ? 0 : Math.min(ach, 100);
              const barClass =
                ach == null
                  ? "bg-gray-300"
                  : ach < 80
                  ? "bg-red-600"
                  : ach < 100
                  ? "bg-amber-600"
                  : "bg-green-600";

              const isExpanded = expandedWitel === w.name;

              return (
                <div
                  key={w.name}
                  className="p-4 bg-gradient-to-r from-gray-50 to-white rounded-lg border border-gray-100 hover:shadow-md transition-all duration-200"
                >
                  <div className="flex justify-between items-center mb-3">
                    <div className="flex items-center gap-3">
                      <div className={`h-3 w-3 rounded-full ${statusDot(ach)} shadow-sm`} />
                      <span className="font-semibold text-gray-900">{w.name}</span>
                      <Badge variant={badgeVariant(ach)} className="text-xs">{badgeTxt}</Badge>
                    </div>
                    <div className="text-right">
                      <div className="text-sm font-bold text-gray-900">
                        {formatIDRCompact(w.revenueM * 1_000_000)}
                      </div>
                      <div className="text-xs text-gray-600">
                        Target: <span className="font-medium">{formatIDRCompact(w.targetM * 1_000_000)}</span>
                      </div>
                    </div>
                  </div>

                  <Progress value={progressVal} className="h-2.5" barClassName={barClass} />

                  <button
                    onClick={() => (isExpanded ? setExpandedWitel(null) : fetchTop10(w.name))}
                    className="mt-3 text-sm text-blue-600 hover:underline disabled:opacity-60"
                    disabled={top10Loading && expandedWitel === w.name}
                  >
                    {isExpanded ? "Tutup" : "Lihat Top 10"}
                  </button>

                  {isExpanded && (
                    <div className="mt-4">
                      <div className="flex items-center justify-between mb-2">
                        <h4 className="text-sm font-semibold text-gray-800">
                          Top 10 CC — {w.name} ({String(month).padStart(2, "0")}/{year})
                          {source === "ngtma" ? " [NGTMA]" : ""}
                        </h4>
                        <span className="text-xs text-gray-500">
                          Persentase dari Total Revenue {w.name}
                        </span>
                      </div>

                      {top10Loading ? (
                        <div className="text-xs text-gray-500">Loading Top 10…</div>
                      ) : top10.length === 0 ? (
                        <div className="text-xs text-gray-500 border rounded-md p-3 bg-gray-50">
                          Tidak ada data untuk bulan/tahun ini.
                        </div>
                      ) : (
                        <ol className="space-y-2">
                          {(() => {
                            const sumVal = top10.reduce((s, r) => s + (toNum(r.total_revenue) || 0), 0);
                            const medalClass = (rank) => {
                              if (rank === 1) return "bg-yellow-400 text-yellow-900";
                              if (rank === 2) return "bg-gray-300 text-gray-800";
                              if (rank === 3) return "bg-amber-400 text-amber-900";
                              return "bg-gray-200 text-gray-700";
                            };

                            return top10.map((row, i) => {
                              const val = toNum(row.total_revenue) || 0;
                              const pct = sumVal ? (val / sumVal) * 100 : 0;
                              const rank = i + 1;
                              return (
                                <li
                                  key={`${row.standard_name}-${i}`}
                                  className="flex items-center justify-between p-2 rounded-lg border border-gray-100 bg-white/70 hover:bg-gray-50 transition"
                                >
                                  <div className="flex items-center gap-3 min-w-0">
                                    <div
                                      className={`h-7 w-7 shrink-0 rounded-full grid place-items-center text-xs font-bold shadow ${medalClass(rank)}`}
                                    >
                                      {rank}
                                    </div>
                                    <span className="truncate text-sm font-medium text-gray-900">
                                      {row.standard_name}
                                    </span>
                                  </div>

                                  <div className="flex items-center gap-3 shrink-0">
                                    <span className="text-xs text-gray-600 font-medium">
                                      {pct.toFixed(1)}%
                                    </span>
                                    <span className="text-sm font-semibold tabular-nums text-gray-900">
                                      {formatIDRCompact(val)}
                                    </span>
                                  </div>
                                </li>
                              );
                            });
                          })()}
                        </ol>
                      )}

                      {!top10Loading && top10.length > 0 && (
                        <div className="mt-3 flex items-center justify-end">
                          <button
                            onClick={() => setExpandedWitel(null)}
                            className="text-xs px-3 py-1.5 rounded-md border border-gray-200 hover:bg-gray-50"
                          >
                            Tutup
                          </button>
                        </div>
                      )}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        )}
      </CardContent>
    </Card>
  );
}
