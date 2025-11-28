import '../../../public/css/inertia.css'
import React, { useEffect, useMemo, useState } from "react";
import { supabase } from "@/lib/supabaseClient";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Progress } from "@/components/ui/progress";
import { Badge } from "@/components/ui/badge";
import { TrendingUp, TrendingDown } from "lucide-react";
import { ScrollArea } from "@/components/ui/scroll-area";
import { formatIDRCompact } from "@/components/ui/formatIDRCompact";

const DIVISOR_TO_M = 1_000_000;

// "DPS" | "DSS" | "DGS"
const COLORS = {
  DPS: "#0070c0",
  DSS: "#203764",
  DGS: "#bf8f00",
};

const toNum = (x) => (x == null ? 0 : typeof x === "string" ? parseFloat(x) : Number(x));
const formatIDRFromMillions = (millions) => formatIDRCompact(millions * 1_000_000);

/* ================== Division totals (supports source) ================== */
async function fetchDivisionMetrics(source = "non-ngtma") {
  const isNG = source === "ngtma";

  const revQueries = isNG
    ? [
        supabase.from("v_dps_ngtma").select("REVENUE_BILL"),
        supabase.from("v_dss_ngtma").select("REVENUE_SOLD"),
        supabase.from("v_dgs_ngtma").select("REVENUE_SOLD"),
      ]
    : [
        supabase.from("v_dps_revenue").select("revenue_bill"),
        supabase.from("v_dss_revenue").select("revenue_sold"),
        supabase.from("v_dgs_revenue").select("revenue_sold"),
      ];

  const [dpsRes, dssRes, dgsRes] = await Promise.all(revQueries);
  if (dpsRes.error) throw dpsRes.error;
  if (dssRes.error) throw dssRes.error;
  if (dgsRes.error) throw dgsRes.error;

  const revDPS =
    (dpsRes.data ?? []).reduce((s, r) => s + toNum(isNG ? r.REVENUE_BILL : r.revenue_bill), 0) /
    DIVISOR_TO_M;
  const revDSS = (dssRes.data ?? []).reduce((s, r) => s + toNum(r.revenue_sold), 0) / DIVISOR_TO_M;
  const revDGS = (dgsRes.data ?? []).reduce((s, r) => s + toNum(r.revenue_sold), 0) / DIVISOR_TO_M;

  // targets (same for NGTMA and Non-NGTMA) -> convert to Millions
  const { data: targets, error: targErr } = await supabase
    .from("v_target_summary")
    .select("DIVISI,total_target");
  if (targErr) throw targErr;

  const tMap = new Map();
  (targets ?? []).forEach((r) =>
    tMap.set(String(r.DIVISI).toUpperCase(), toNum(r.total_target) / DIVISOR_TO_M)
  );

  const rows = [
    { name: "DPS", revenueM: revDPS, targetM: tMap.get("DPS") ?? 0, color: COLORS.DPS },
    { name: "DSS", revenueM: revDSS, targetM: tMap.get("DSS") ?? 0, color: COLORS.DSS },
    { name: "DGS", revenueM: revDGS, targetM: tMap.get("DGS") ?? 0, color: COLORS.DGS },
  ].map((r) => ({
    ...r,
    achievementPct: r.targetM > 0 ? (r.revenueM / r.targetM) * 100 : 0,
  }));

  return rows;
}

// proses segmen
function processSegments(rows) {
  const segments = (rows ?? []).map((r) => {
    const revenue = toNum(r.revenue_total);
    const target = toNum(r.target_total);
    return {
      name: r.lsegment_ho || "Unknown",
      revenue, // full rupiah
      target, // full rupiah
      percentage: target > 0 ? (revenue / target) * 100 : 0,
      target_gap: target > 0 ? ((revenue - target) / target) * 100 : 0,
      share: 0,
    };
  });

  segments.sort((a, b) => b.revenue - a.revenue);
  const totalRevenue = segments.reduce((s, v) => s + v.revenue, 0);
  return segments.map((s) => ({
    ...s,
    share: totalRevenue > 0 ? (s.revenue / totalRevenue) * 100 : 0,
  }));
}

async function buildSegmentTargetMaps() {
  const { data, error } = await supabase
    .from("target_data")
    .select("DIVISI,LSEGMENT_HO,TARGET");
  if (error) throw error;

  const mk = () => new Map();
  const out = { DPS: mk(), DSS: mk(), DGS: mk() };

  for (const r of data ?? []) {
    const div = String(r.DIVISI ?? "").toUpperCase();
    const seg = r.LSEGMENT_HO ?? null;
    const val = toNum(r.TARGET);
    if (!seg || !div || !out[div]) continue;
    out[div].set(seg, (out[div].get(seg) ?? 0) + val);
  }
  return out;
}

async function fetchSegmentData(source = "non-ngtma") {
  if (source === "non-ngtma") {
    const [dpsRes, dssRes, dgsRes] = await Promise.all([
      supabase.from("v_dps_lsegment_totals").select("lsegment_ho,revenue_total,target_total"),
      supabase.from("v_dss_lsegment_totals").select("lsegment_ho,revenue_total,target_total"),
      supabase.from("v_dgs_lsegment_totals").select("lsegment_ho,revenue_total,target_total"),
    ]);
    if (dpsRes.error || dssRes.error || dgsRes.error) {
      throw dpsRes.error || dssRes.error || dgsRes.error;
    }

    return {
      DPS: processSegments(dpsRes.data),
      DSS: processSegments(dssRes.data),
      DGS: processSegments(dgsRes.data),
    };
  }

  const [dpsNG, dssNG, dgsNG, tMaps] = await Promise.all([
    supabase.from("v_dps_ngtma").select("LSEGMENT_HO,REVENUE_BILL"),
    supabase.from("v_dss_ngtma").select("LSEGMENT_HO,REVENUE_SOLD"),
    supabase.from("v_dgs_ngtma").select("LSEGMENT_HO,REVENUE_SOLD"),
    buildSegmentTargetMaps(),
  ]);
  if (dpsNG.error) throw dpsNG.error;
  if (dssNG.error) throw dssNG.error;
  if (dgsNG.error) throw dgsNG.error;

  const sumBySeg = (rows, col) => {
    const map = new Map();
    (rows ?? []).forEach((r) => {
      const seg = r.LSEGMENT_HO;
      if (!seg) return;
      map.set(seg, (map.get(seg) ?? 0) + toNum(r[col]));
    });
    return map;
  };

  const dpsMap = sumBySeg(dpsNG.data, "REVENUE_BILL");
  const dssMap = sumBySeg(dssNG.data, "REVENUE_SOLD");
  const dgsMap = sumBySeg(dgsNG.data, "REVENUE_SOLD");

  const attachTargets = (map, div) =>
    Array.from(map.entries()).map(([seg, rev]) => ({
      lsegment_ho: seg,
      revenue_total: rev, // full rupiah
      target_total: toNum(tMaps[div].get(seg) ?? 0), // full rupiah
    }));

  return {
    DPS: processSegments(attachTargets(dpsMap, "DPS")),
    DSS: processSegments(attachTargets(dssMap, "DSS")),
    DGS: processSegments(attachTargets(dgsMap, "DGS")),
  };
}

export function DivisionOverview() {
  const [source, setSource] = useState("non-ngtma");
  const [divisions, setDivisions] = useState(null);
  const [segmentData, setSegmentData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    (async () => {
      setLoading(true);
      setError(null);
      try {
        const [divRows, segRows] = await Promise.all([
          fetchDivisionMetrics(source),
          fetchSegmentData(source),
        ]);
        setDivisions(divRows);
        setSegmentData(segRows);
      } catch (e) {
        setError(e?.message ?? "Failed to load division metrics");
      } finally {
        setLoading(false);
      }
    })();
  }, [source]);

  const totalRevenueM = useMemo(
    () => (divisions ?? []).reduce((s, r) => s + r.revenueM, 0),
    [divisions]
  );

  const progressSegments = useMemo(() => {
    if (!divisions || totalRevenueM <= 0) return [];
    let cum = 0;
    return divisions.map((d) => {
      const width = (d.revenueM / totalRevenueM) * 100;
      const seg = { ...d, startPercentage: cum, width };
      cum += width;
      return seg;
    });
  }, [divisions, totalRevenueM]);

  const renderSegmentDetails = (segments, divisionName) => {
    const totalSegmentRevenue = segments.reduce((sum, s) => sum + s.revenue, 0);
    const totalSegmentTarget  = segments.reduce((sum, s) => sum + s.target, 0);
    const overallAchievement  =
      totalSegmentTarget > 0 ? (totalSegmentRevenue / totalSegmentTarget) * 100 : 0;

    const colors = ["#ef4444", "#f97316", "#eab308", "#22c55e", "#3b82f6", "#8b5cf6"];

    const divisionBg = {
      DPS: "#0070c0",
      DSS: "#203764",
      DGS: "#bf8f00",
    }[divisionName] || "#6b7280";

    const ACHIEVEMENT_STYLES = {
      poor:   { badge: "bg-red-500 text-white" },
      warn:   { badge: "bg-amber-500 text-white" },
      good:   { badge: "bg-green-500 text-white" },
      unknown:{ badge: "bg-gray-400 text-white" },
    };

    let status = "unknown";
    if (overallAchievement < 80) status = "poor";
    else if (overallAchievement < 100) status = "warn";
    else status = "good";

    return (
      <div className="space-y-4">
        <div className="p-4 rounded-lg border bg-gray-50">
          <div className="flex items-center justify-between mb-3">
            <h3 className="font-bold text-base text-gray-900">
              {divisionName} Division Summary
            </h3>
            <Badge className={`text-sm border-0 ${ACHIEVEMENT_STYLES[status].badge}`}>
              {overallAchievement.toFixed(1)}% Achievement
            </Badge>
          </div>
          <div className="grid grid-cols-3 gap-3 text-center">
            <div>
              <div className="text-xl font-bold" style={{ color: divisionBg }}>
                {formatIDRCompact(totalSegmentRevenue)}
              </div>
              <div className="text-xs text-gray-500">Total Revenue</div>
            </div>
            <div>
              <div className="text-xl font-bold" style={{ color: divisionBg }}>
                {formatIDRCompact(totalSegmentTarget)}
              </div>
              <div className="text-xs text-gray-500">Total Target</div>
            </div>
            <div>
              <div className="text-xl font-bold" style={{ color: divisionBg }}>
                {segments.length}
              </div>
              <div className="text-xs text-gray-500">Active Segments</div>
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
          {segments.map((segment, i) => (
            <div
              key={segment.name}
              className="p-3 bg-white rounded-lg border border-gray-200 hover:shadow-sm transition-all duration-200 h-full"
            >
              <div className="flex items-center justify-between mb-2">
                <div className="flex items-center gap-2">
                  <div
                    className="w-3 h-3 rounded-full"
                    style={{ backgroundColor: colors[i % colors.length] }}
                  />
                  <h4 className="font-medium text-sm text-gray-900 leading-tight">
                    {segment.name}
                  </h4>
                  {segment.target_gap >= 15 ? (
                    <TrendingUp className="h-3 w-3 text-green-500 flex-shrink-0" />
                  ) : segment.target_gap < 10 ? (
                    <TrendingDown className="h-3 w-3 text-red-500 flex-shrink-0" />
                  ) : null}
                </div>

                <Badge
                  variant={segment.percentage >= 100 ? "default" : "outline"}
                  className="text-xs"
                >
                  {segment.percentage.toFixed(1)}%
                </Badge>
              </div>

              <div className="grid grid-cols-5 gap-3 mb-2 text-xs">
                <div>
                  <div className="font-bold text-gray-900">
                    {formatIDRCompact(segment.revenue)}
                  </div>
                  <div className="text-xs text-gray-500">Revenue</div>
                </div>
                <div>
                  <div className="font-bold text-gray-700">
                    {formatIDRCompact(segment.target)}
                  </div>
                  <div className="text-xs text-gray-500">Target</div>
                </div>
                <div>
                  <div className="font-bold text-green-600">
                    +{segment.target_gap.toFixed(1)}%
                  </div>
                  <div className="text-xs text-gray-500">Target Gap</div>
                </div>
                <div>
                  <div className="font-bold text-blue-600">
                    {segment.share.toFixed(1)}%
                  </div>
                  <div className="text-xs text-gray-500">Share</div>
                </div>
                <div>
                  <div className="font-bold text-gray-600">
                    {segment.percentage.toFixed(1)}%
                  </div>
                  <div className="text-xs text-gray-500">Achievement</div>
                </div>
              </div>

              <Progress value={Math.min(segment.percentage, 100)} className="h-1.5" />
            </div>
          ))}
        </div>
      </div>
    );
  };

  return (
    <Card className="shadow-sm border-gray-200 h-full flex flex-col">
      <CardHeader className="pb-4 flex-shrink-0">
        <div className="flex items-start justify-between gap-3">
          <div>
            <CardTitle className="text-lg font-bold text-gray-900">
              Overview Divisi &amp; Segmen Industri (YTD)
            </CardTitle>
            <p className="text-sm text-gray-400">
              Performansi Divisi dan Segmen Industri Sepanjang Waktu
              {source === "ngtma" ? " (NGTMA)" : ""}
            </p>
          </div>

          <div className="mt-1">
            <label className="text-xs text-gray-600 mr-2">Source:</label>
            <select
              className="border rounded-lg px-2 py-1 text-xs w-28"
              value={source}
              onChange={(e) => setSource(e.target.value)}
            >
              <option value="non-ngtma">Non-NGTMA</option>
              <option value="ngtma">NGTMA</option>
            </select>
          </div>
        </div>
      </CardHeader>

      <CardContent className="flex-1 flex flex-col">
        {loading && <div className="text-sm text-gray-600">Loading summary…</div>}
        {error && <div className="text-sm text-red-600">Error: {error}</div>}

        {!loading && !error && divisions && (
          <div className="space-y-6 flex-1">
            <div className="text-center">
              <h3 className="text-3xl font-bold text-gray-900">
                {formatIDRFromMillions(totalRevenueM)}
              </h3>
              <p className="text-sm text-gray-600 font-medium">Total Revenue YTD</p>
            </div>

            <div className="space-y-4">
              <div className="relative h-8 bg-gray-200 rounded-full overflow-hidden">
                {progressSegments.map((seg) => (
                  <div
                    key={seg.name}
                    className="absolute h-full transition-all duration-500 ease-in-out flex items-center justify-center"
                    style={{
                      left: `${seg.startPercentage}%`,
                      width: `${seg.width}%`,
                      backgroundColor: seg.color,
                    }}
                  >
                    {seg.width > 15 && (
                      <span className="text-white text-xs font-bold">{seg.name}</span>
                    )}
                  </div>
                ))}
              </div>

              <div className="grid grid-cols-3 gap-4">
                {divisions.map((d) => {
                  const contributionPct =
                    totalRevenueM > 0 ? (d.revenueM / totalRevenueM) * 100 : 0;
                  return (
                    <div key={d.name} className="text-center p-3 bg-gray-50 rounded-lg">
                      <div className="flex items-center justify-center gap-2 mb-2">
                        <div
                          className="w-3 h-3 rounded-full"
                          style={{ backgroundColor: d.color }}
                        />
                        <span className="text-sm font-bold text-gray-900">{d.name}</span>
                      </div>
                      <div className="space-y-1">
                        <div className="text-lg font-bold" style={{ color: d.color }}>
                          {formatIDRFromMillions(d.revenueM)}
                        </div>
                        <div className="text-xs text-gray-600">
                          {contributionPct.toFixed(1)}% dari total
                        </div>
                        <div className="text-xs">
                          <span
                            className={
                              d.achievementPct >= 100 ? "text-green-600" : "text-red-600"
                            }
                          >
                            {d.achievementPct.toFixed(1)}% target
                          </span>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>

            <div className="border-t border-gray-200 pt-6 flex-1">
              <h3 className="text-base font-bold text-gray-900 mb-4">Analisa Segmen Industri</h3>
              <Tabs defaultValue="DPS" className="w-full h-full flex flex-col">
                <TabsList className="grid w-full grid-cols-3 mb-4 flex-shrink-0">
                  <TabsTrigger value="DPS" className={({ selected }) =>
                      selected ? 'font-medium text-xs text-red-600' : 'font-medium text-xs text-black'
                    }
                  > DPS (Private)
                  </TabsTrigger>
                  <TabsTrigger value="DSS" className={({ selected }) =>
                      selected ? 'font-medium text-xs text-red-600' : 'font-medium text-xs text-black'
                    }
                  > DSS (BUMN/Korporasi)
                  </TabsTrigger>
                  <TabsTrigger value="DGS" className={({ selected }) =>
                      selected ? 'font-medium text-xs text-red-600' : 'font-medium text-xs text-black'
                    }
                  > DGS (Government)
                  </TabsTrigger>
                </TabsList>

                <div className="flex-1">
                  <TabsContent value="DPS" className="mt-0 h-full">
                    {segmentData && renderSegmentDetails(segmentData.DPS, "DPS")}
                  </TabsContent>
                  <TabsContent value="DSS" className="mt-0 h-full">
                    {segmentData && renderSegmentDetails(segmentData.DSS, "DSS")}
                  </TabsContent>
                  <TabsContent value="DGS" className="mt-0 h-full">
                    {segmentData && renderSegmentDetails(segmentData.DGS, "DGS")}
                  </TabsContent>
                </div>
              </Tabs>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
