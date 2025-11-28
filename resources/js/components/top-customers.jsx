import '../../../public/css/inertia.css'
import React, { useEffect, useMemo, useRef, useState } from "react";
import { supabase } from "@/lib/supabaseClient";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Crown, Building, Building2, Landmark } from "lucide-react";
import { formatIDRCompact } from "@/components/ui/formatIDRCompact";

const DIVISIONS = ["DPS", "DSS", "DGS"];

const REVENUE_COL_MAP = {
  DPS: "revenue_bill",
  DSS: "revenue_sold",
  DGS: "revenue_sold",
};
const TOP10_VIEW_MAP = {
  DPS: "v_dps_top10",
  DSS: "v_dss_top10",
  DGS: "v_dgs_top10",
};
const FULL_VIEW_MAP = {
  DPS: "v_dps_revenue",
  DSS: "v_dss_revenue",
  DGS: "v_dgs_revenue",
};

// NGTMA views + columns
const NGTMA_VIEW_MAP = {
  DPS: { view: "v_dps_ngtma", witelCol: "WITEL_BILL", revenueCol: "REVENUE_BILL" },
  DSS: { view: "v_dss_ngtma", witelCol: "WITEL_HO",   revenueCol: "REVENUE_SOLD" },
  DGS: { view: "v_dgs_ngtma", witelCol: "WITEL_HO",   revenueCol: "REVENUE_SOLD" },
};

function toNum(x) {
  if (x == null) return 0;
  if (typeof x === "number") return x;
  const n = parseFloat(String(x));
  return isFinite(n) ? n : 0;
}

export function TopCustomers() {
  const [division, setDivision] = useState("DPS");
  const [source, setSource] = useState("non-ngtma"); // "non-ngtma" | "ngtma"

  const [loading, setLoading] = useState(false);
  const [rows, setRows] = useState([]);
  const [error, setError] = useState(null);

  const cacheRef = useRef({});
  const reqIdRef = useRef(0);

  const fetchOneNon = async (div) => {
    const revenueCol = REVENUE_COL_MAP[div];

    let { data, error } = await supabase
      .from(TOP10_VIEW_MAP[div])
      .select(`NIP_NAS,STANDARD_NAME,WITEL_HO,${revenueCol}`)
      .order(revenueCol, { ascending: false });

    if (error?.code === "42P01") {
      const resp = await supabase
        .from(FULL_VIEW_MAP[div])
        .select(`NIP_NAS,STANDARD_NAME,WITEL_HO,${revenueCol}`)
        .order(revenueCol, { ascending: false })
        .limit(10);
      data = resp.data;
      error = resp.error;
    }
    if (error) throw error;

    return (data ?? []).map((r) => ({
      NIP_NAS: r.NIP_NAS,
      STANDARD_NAME: r.STANDARD_NAME,
      WITEL_HO: r.WITEL_HO,
      REVENUE: toNum(r[revenueCol]),
    }));
  };

  // NGTMA: aggregate from v_ngtma
  const fetchOneNgtma = async (div) => {
    const cfg = NGTMA_VIEW_MAP[div];
    const { view, witelCol, revenueCol } = cfg;

    const { data, error } = await supabase
      .from(view)
      .select(`NIP_NAS,STANDARD_NAME,${witelCol},${revenueCol}`);
    if (error) throw error;

    // Group by
    const group = new Map();
    for (const r of data ?? []) {
      const nip = r.NIP_NAS;
      const name = r.STANDARD_NAME;
      const witel = r[witelCol] ?? null;
      const rev = toNum(r[revenueCol]);

      const key = `${nip}||${name}||${witel ?? ""}`;
      const prev = group.get(key) ?? 0;
      group.set(key, prev + rev);
    }

    const rows = [...group.entries()]
      .map(([key, REVENUE]) => {
        const [NIP_NAS, STANDARD_NAME, WITEL_HO] = key.split("||");
        return { NIP_NAS, STANDARD_NAME, WITEL_HO: WITEL_HO || null, REVENUE };
      })
      .sort((a, b) => b.REVENUE - a.REVENUE)
      .slice(0, 10);

    return rows;
  };

  const fetchOne = async (div, src) => {
    return src === "ngtma" ? fetchOneNgtma(div) : fetchOneNon(div);
  };

  const prefetchAll = async (src) => {
    const myReqId = ++reqIdRef.current;
    setError(null);
    setLoading(true);
    try {
      const results = await Promise.allSettled(DIVISIONS.map((d) => fetchOne(d, src)));
      const map = {};
      results.forEach((res, idx) => {
        const d = DIVISIONS[idx];
        if (res.status === "fulfilled") map[`${src}:${d}`] = res.value;
      });

      if (myReqId === reqIdRef.current) {
        cacheRef.current = { ...cacheRef.current, ...map };
        const k = `${src}:${division}`;
        if (cacheRef.current[k]) setRows(cacheRef.current[k]);
      }
    } catch (e) {
      if (myReqId === reqIdRef.current) setError(e?.message ?? "Prefetch failed");
    } finally {
      if (myReqId === reqIdRef.current) setLoading(false);
    }
  };

  useEffect(() => {
    prefetchAll(source);
    const onFocus = () => {
      const missing = DIVISIONS.some((d) => !cacheRef.current[`${source}:${d}`]);
      if (missing) prefetchAll(source);
    };
    window.addEventListener("focus", onFocus);
    return () => window.removeEventListener("focus", onFocus);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [source]);

  useEffect(() => {
    let cancelled = false;
    const run = async () => {
      setError(null);
      const key = `${source}:${division}`;
      const cached = cacheRef.current[key];
      if (cached) {
        setRows(cached);
        return;
      }
      setLoading(true);
      const myReq = ++reqIdRef.current;
      try {
        const data = await fetchOne(division, source);
        if (!cancelled && myReq === reqIdRef.current) {
          cacheRef.current[key] = data;
          setRows(data);
        }
      } catch (e) {
        if (!cancelled && myReq === reqIdRef.current) {
          setRows([]);
          setError(e?.message ?? "Failed to load data");
        }
      } finally {
        if (!cancelled && myReq === reqIdRef.current) setLoading(false);
      }
    };
    run();
    return () => { cancelled = true; };
  }, [division, source]);

  /* ================== memo ================== */
  const top10 = useMemo(() => {
    if (!rows.length) return [];
    return rows.slice(0, 10).map((r) => ({
      NIP_NAS: r.NIP_NAS,
      STANDARD_NAME: r.STANDARD_NAME,
      WITEL_HO: r.WITEL_HO,
      REVENUE: toNum(r.REVENUE),
    }));
  }, [rows]);

  /* ================== UI ================== */
  const renderCustomerList = (customers, icon) => (
    <div className="space-y-3">
      {customers.map((customer, idx) => (
        <div
          key={`${customer.NIP_NAS}-${idx}`}
          className="flex items-center justify-between p-3 rounded-lg border hover:shadow-sm transition-all duration-200"
        >
          <div className="flex items-center space-x-3">
            <div className="flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-sm font-bold flex-shrink-0">
              {idx + 1}
            </div>
            {idx < 3 && <Crown className="h-4 w-4 text-yellow-500 flex-shrink-0" />}
            <div className="flex-shrink-0">{icon}</div>
            <div className="min-w-0 flex-1">
              <div className="font-medium text-sm text-gray-900 truncate">{customer.STANDARD_NAME}</div>
              <div className="text-xs text-gray-500">{customer.WITEL_HO}</div>
            </div>
          </div>
          <div className="text-right space-y-1 flex-shrink-0">
            <div className="font-bold text-sm">{formatIDRCompact(customer.REVENUE)} IDR</div>
          </div>
        </div>
      ))}
      {!customers.length && !loading && !error && (
        <div className="text-sm text-gray-500">No data found.</div>
      )}
      {error && <div className="text-sm text-red-600">Error: {error}</div>}
    </div>
  );

  return (
    <Card className="shadow-m border-gray-200 bg-white h-full flex flex-col">
      <CardHeader className="pb-4 border-b border-gray-200 flex-shrink-0">
        <div className="flex items-start justify-between gap-3">
          <div className="flex-1">
            <CardTitle className="text-lg font-bold text-gray-900">Top 10 Pelanggan per Divisi</CardTitle>
            <p className="text-sm text-gray-400">
              Peringkat Revenue Pelanggan Sepanjang Waktu
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
        <Tabs value={division} onValueChange={(v) => setDivision(v)} className="w-full h-full flex flex-col">
          <TabsList className="grid w-full grid-cols-3 mb-4 flex-shrink-0">
            <TabsTrigger value="DPS" className="text-xs">DPS (Private)</TabsTrigger>
            <TabsTrigger value="DSS" className="text-xs">DSS (BUMN/Korporasi)</TabsTrigger>
            <TabsTrigger value="DGS" className="text-xs">DGS (Government)</TabsTrigger>
          </TabsList>

          <div className="flex-1">
            <TabsContent value="DPS" className="mt-0 h-full">
              {loading && !cacheRef.current[`${source}:DPS`]
                ? "Loading..."
                : renderCustomerList(top10, <Building className="h-4 w-4 text-sky-700" />)}
            </TabsContent>

            <TabsContent value="DSS" className="mt-0 h-full">
              {loading && !cacheRef.current[`${source}:DSS`]
                ? "Loading..."
                : renderCustomerList(top10, <Building2 className="h-4 w-4 text-blue-900" />)}
            </TabsContent>

            <TabsContent value="DGS" className="mt-0 h-full">
              {loading && !cacheRef.current[`${source}:DGS`]
                ? "Loading..."
                : renderCustomerList(top10, <Landmark className="h-4 w-4 text-amber-700" />)}
            </TabsContent>
          </div>
        </Tabs>
      </CardContent>
    </Card>
  );
}
