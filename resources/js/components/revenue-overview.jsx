import '../../../public/css/inertia.css'
import React, { useEffect, useRef, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { formatIDRCompact } from "@/components/ui/formatIDRCompact";
import { MetricCard } from "@/components/metric-card";
import { supabase } from "@/lib/supabaseClient";
import { Building2, Building, Landmark, Target, DollarSign } from "lucide-react";

const ICONS = {
  RLEGS3: Target,
  DPS: Building,
  DSS: Building2,
  DGS: Landmark,
};

const THEME = {
  RLEGS3: { bg: "bg-gray-50", text: "text-red-700", iconBg: "bg-red-700" },
  DPS: { bg: "bg-gray-50", text: "text-sky-700", iconBg: "bg-sky-700" },
  DSS: { bg: "bg-gray-50", text: "text-blue-900", iconBg: "bg-blue-900" },
  DGS: { bg: "bg-gray-50", text: "text-amber-700", iconBg: "bg-amber-700" },
};

const toNum = (v) => (v == null ? 0 : typeof v === "number" ? v : parseFloat(String(v)));

function getCurrentYearMonth() {
  const now = new Date();
  return { year: now.getFullYear(), month: now.getMonth() + 1 };
}

export function RevenueOverview() {
  const [{ year, month }, setFilter] = useState(getCurrentYearMonth());
  const [source, setSource] = useState("non-ngtma"); 
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [revenueData, setRevenueData] = useState([]);

  const monthlyNonRef = useRef({});
  const monthlyNgtmaRef = useRef({}); 
  const targetsRef = useRef(null);
  const reqIdRef = useRef(0);

  const buildCards = (y, m) => {
    const dataRef = source === "ngtma" ? monthlyNgtmaRef : monthlyNonRef;
    const monthsY = dataRef.current[y];
    if (!monthsY || !targetsRef.current) return [];

    // prev month for percent
    let prevMonth = m - 1;
    let prevYear = y;
    if (prevMonth < 1) { prevMonth = 12; prevYear = y - 1; }
    const monthsPrev = dataRef.current[prevYear];

    const def = { dps: 0, dss: 0, dgs: 0, total: 0 };
    const curr = monthsY[m - 1] ?? def;
    const prev = monthsPrev ? monthsPrev[prevMonth - 1] ?? def : def;

    const mom = (c, p) => (p > 0 ? ((c - p) / p) * 100 : 0);
    const T = targetsRef.current;

    if (source === "ngtma") {
      return [
        {
          title: "RLEGS3 (NGTMA)",
          value: formatIDRCompact(curr.total),
          percentage: prev.total > 0 ? (curr.total / prev.total) * 100 : 0,
          target: formatIDRCompact(T.RLEGS3),
          targetType: mom(curr.total, prev.total) >= 0 ? "positive" : "negative",
          ytd: `${mom(curr.total, prev.total).toFixed(1)}%`,
          icon: ICONS.RLEGS3,
        },
        {
          title: "DPS (NGTMA)",
          value: formatIDRCompact(curr.dps),
          percentage: prev.dps > 0 ? (curr.dps / prev.dps) * 100 : 0,
          target: formatIDRCompact(T.DPS),
          targetType: mom(curr.dps, prev.dps) >= 0 ? "positive" : "negative",
          ytd: `${mom(curr.dps, prev.dps).toFixed(1)}%`,
          icon: ICONS.DPS,
        },
        {
          title: "DSS (NGTMA)",
          value: formatIDRCompact(curr.dss),
          percentage: prev.dss > 0 ? (curr.dss / prev.dss) * 100 : 0,
          target: formatIDRCompact(T.DSS),
          targetType: mom(curr.dss, prev.dss) >= 0 ? "positive" : "negative",
          ytd: `${mom(curr.dss, prev.dss).toFixed(1)}%`,
          icon: ICONS.DSS,
        },
        {
          title: "DGS (NGTMA)",
          value: formatIDRCompact(curr.dgs),
          percentage: prev.dgs > 0 ? (curr.dgs / prev.dgs) * 100 : 0,
          target: formatIDRCompact(T.DGS),
          targetType: mom(curr.dgs, prev.dgs) >= 0 ? "positive" : "negative",
          ytd: `${mom(curr.dgs, prev.dgs).toFixed(1)}%`,
          icon: ICONS.DGS,
        },
      ];
    }

    // Non-NGTMA
    return [
      {
        title: "RLEGS3",
        value: formatIDRCompact(curr.total),
        percentage: prev.total > 0 ? (curr.total / prev.total) * 100 : 0,
        target: formatIDRCompact(T.RLEGS3),
        targetType: mom(curr.total, prev.total) >= 0 ? "positive" : "negative",
        ytd: `${mom(curr.total, prev.total).toFixed(1)}%`,
        icon: ICONS.RLEGS3,
      },
      {
        title: "DPS",
        value: formatIDRCompact(curr.dps),
        percentage: prev.dps > 0 ? (curr.dps / prev.dps) * 100 : 0,
        target: formatIDRCompact(T.DPS),
        targetType: mom(curr.dps, prev.dps) >= 0 ? "positive" : "negative",
        ytd: `${mom(curr.dps, prev.dps).toFixed(1)}%`,
        icon: ICONS.DPS,
      },
      {
        title: "DSS",
        value: formatIDRCompact(curr.dss),
        percentage: prev.dss > 0 ? (curr.dss / prev.dss) * 100 : 0,
        target: formatIDRCompact(T.DSS),
        targetType: mom(curr.dss, prev.dss) >= 0 ? "positive" : "negative",
        ytd: `${mom(curr.dss, prev.dss).toFixed(1)}%`,
        icon: ICONS.DSS,
      },
      {
        title: "DGS",
        value: formatIDRCompact(curr.dgs),
        percentage: prev.dgs > 0 ? (curr.dgs / prev.dgs) * 100 : 0,
        target: formatIDRCompact(T.DGS),
        targetType: mom(curr.dgs, prev.dgs) >= 0 ? "positive" : "negative",
        ytd: `${mom(curr.dgs, prev.dgs).toFixed(1)}%`,
        icon: ICONS.DGS,
      },
    ];
  };

  /* Target */
  const prefetchTargets = async () => {
    if (targetsRef.current) return;
    const { data, error } = await supabase.from("v_target_summary").select("DIVISI,total_target");
    if (error) throw error;

    let DPS = 0, DSS = 0, DGS = 0, RLEGS3 = 0;
    for (const r of data ?? []) {
      const div = String(r.DIVISI ?? "").toUpperCase();
      const val = toNum(r.total_target);
      RLEGS3 += val;
      if (div === "DPS") DPS = val;
      else if (div === "DSS") DSS = val;
      else if (div === "DGS") DGS = val;
    }
    targetsRef.current = { DPS, DSS, DGS, RLEGS3 };
  };

  const prefetchYearNon = async (y) => {
    if (monthlyNonRef.current[y]) return;
    const { data, error } = await supabase
      .from("v_monthly_witel_revenue")
      .select("year,month,dps,dss,dgs,total_revenue")
      .eq("year", y);
    if (error) throw error;

    const months = Array.from({ length: 12 }, () => ({ dps: 0, dss: 0, dgs: 0, total: 0 }));
    for (const r of data ?? []) {
      const idx = Math.max(0, Math.min(11, toNum(r.month) - 1));
      months[idx].dps += toNum(r.dps);
      months[idx].dss += toNum(r.dss);
      months[idx].dgs += toNum(r.dgs);
      months[idx].total += toNum(r.total_revenue);
    }
    monthlyNonRef.current[y] = months;
  };

  /* NGTMA */
  const prefetchYearNgtma = async (y) => {
    if (monthlyNgtmaRef.current[y]) return;

    const [dpsRes, dssRes, dgsRes] = await Promise.all([
      supabase.from("v_dps_ngtma").select("YEAR,MONTH,REVENUE_BILL").eq("YEAR", y),
      supabase.from("v_dss_ngtma").select("YEAR,MONTH,REVENUE_SOLD").eq("YEAR", y),
      supabase.from("v_dgs_ngtma").select("YEAR,MONTH,REVENUE_SOLD").eq("YEAR", y),
    ]);
    if (dpsRes.error) throw dpsRes.error;
    if (dssRes.error) throw dssRes.error;
    if (dgsRes.error) throw dgsRes.error;

    const months = Array.from({ length: 12 }, () => ({ dps: 0, dss: 0, dgs: 0, total: 0 }));
    for (const r of dpsRes.data ?? []) {
      const idx = Math.max(0, Math.min(11, toNum(r.MONTH) - 1));
      const v = toNum(r.REVENUE_BILL);
      months[idx].dps += v;
      months[idx].total += v;
    }
    for (const r of dssRes.data ?? []) {
      const idx = Math.max(0, Math.min(11, toNum(r.MONTH) - 1));
      const v = toNum(r.REVENUE_SOLD);
      months[idx].dss += v;
      months[idx].total += v;
    }
    for (const r of dgsRes.data ?? []) {
      const idx = Math.max(0, Math.min(11, toNum(r.MONTH) - 1));
      const v = toNum(r.REVENUE_SOLD);
      months[idx].dgs += v;
      months[idx].total += v;
    }

    monthlyNgtmaRef.current[y] = months;
  };

  /* load initiall*/
  useEffect(() => {
    let canceled = false;
    const run = async () => {
      const myReq = ++reqIdRef.current;
      setLoading(true);
      setError(null);
      try {
        const { year: curY } = getCurrentYearMonth();
        await Promise.all([
          prefetchTargets(),
          prefetchYearNon(curY),
          prefetchYearNon(curY - 1),
          prefetchYearNgtma(curY),
          prefetchYearNgtma(curY - 1),
        ]);
        if (!canceled && myReq === reqIdRef.current) {
          setRevenueData(buildCards(year, month));
        }
      } catch (e) {
        if (!canceled && myReq === reqIdRef.current) setError(e?.message ?? "Failed to load data");
      } finally {
        if (!canceled && myReq === reqIdRef.current) setLoading(false);
      }
    };
    run();
    return () => { canceled = true; };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    let canceled = false;
    const run = async () => {
      const myReq = ++reqIdRef.current;
      setError(null);

      const prevY = month === 1 ? year - 1 : year;
      const needs = [];
      if (!targetsRef.current) needs.push(prefetchTargets());

      if (source === "non-ngtma") {
        if (!monthlyNonRef.current[year]) needs.push(prefetchYearNon(year));
        if (!monthlyNonRef.current[prevY]) needs.push(prefetchYearNon(prevY));
      } else {
        if (!monthlyNgtmaRef.current[year]) needs.push(prefetchYearNgtma(year));
        if (!monthlyNgtmaRef.current[prevY]) needs.push(prefetchYearNgtma(prevY));
      }

      if (needs.length) setLoading(true);
      try {
        if (needs.length) await Promise.all(needs);
        if (!canceled && myReq === reqIdRef.current) setRevenueData(buildCards(year, month));
      } catch (e) {
        if (!canceled && myReq === reqIdRef.current) setError(e?.message ?? "Failed to load data");
      } finally {
        if (!canceled && myReq === reqIdRef.current) setLoading(false);
      }
    };
    run();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [year, source]);

  /* -------- when month changes -------- */
  useEffect(() => {
    setRevenueData(buildCards(year, month));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [month]);

  const years = Array.from({ length: 5 }, (_, i) => getCurrentYearMonth().year - i);
  const months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];

  return (
    <div className="space-y-6">
      <Card className="border-gray-200 bg-gradient-to-r from-white to-gray-50">
        <CardHeader className="pb-4 border-gray-200 flex items-center justify-between">
          <CardTitle className="text-base font-bold flex items-center gap-3 text-gray-800">
            <div className="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
              <DollarSign className="h-4 w-4 text-green-700" />
            </div>
            Revenue Performance (MTD)
          </CardTitle>

          <div className="mt-4 flex items-center gap-4">
            <label className="text-sm text-gray-600">Year:</label>
            <select
              className="border rounded-lg px-2 py-1 text-sm w-20 focus:border-2 focus:border-red-600"
              value={year}
              onChange={(e) => setFilter((f) => ({ ...f, year: Number(e.target.value) }))}
            >
              {years.map((y) => (
                <option key={y} value={y}>{y}</option>
              ))}
            </select>

            <label className="text-sm text-gray-700 font-medium">Month:</label>
            <select
              className="border rounded-lg px-2 py-1 text-sm w-20 focus:border-2 focus:border-red-600"
              value={month}
              onChange={(e) => setFilter((f) => ({ ...f, month: Number(e.target.value) }))}
            >
              {months.map((m, i) => (
                <option key={m} value={i + 1}>{m}</option>
              ))}
            </select>

            <label className="text-sm text-gray-700 font-medium">Source:</label>
            <select
              className="border rounded-lg px-2 py-1 text-sm w-32 focus:border-2 focus:border-red-600"
              value={source}
              onChange={(e) => setSource(e.target.value)}
            >
              <option value="non-ngtma">Non-NGTMA</option>
              <option value="ngtma">NGTMA</option>
            </select>
          </div>
        </CardHeader>

        <CardContent className="p-4">
          {loading && !(source === "ngtma" ? monthlyNgtmaRef.current[year] : monthlyNonRef.current[year]) && (
            <div className="text-sm text-gray-600">Loading...</div>
          )}
          {error && <div className="text-sm text-red-600">Error: {error}</div>}

          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            {revenueData.map((item, index) => {
              const baseKey = item.title.replace(" (NGTMA)", "");
              const theme = THEME[baseKey] ?? { bg: "bg-gray-50", text: "text-gray-800", iconBg: "bg-gray-700" };
              const Icon = item.icon;
              return (
                <div
                  key={item.title}
                  className="animate-slide-up"
                  style={{ animationDelay: `${index * 150}ms`, animationFillMode: "both" }}
                >
                  <MetricCard
                    title={item.title}
                    value={item.value}
                    percentage={item.percentage}
                    target={item.target}
                    targetType={item.targetType}
                    ytd={item.ytd}
                    icon={Icon}
                    bgClass={theme.bg}
                    accentTextClass={theme.text}
                    iconBgClass={theme.iconBg}
                  />
                </div>
              );
            })}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
