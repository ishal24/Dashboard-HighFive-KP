import '../../../public/css/inertia.css'
import React, { useEffect, useMemo, useRef, useState } from "react";
import { X } from "lucide-react";

import { supabase } from "@/lib/supabaseClient";
import { formatIDRCompact } from "@/components/ui/formatIDRCompact";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from "@/components/ui/command";
import { Badge } from "@/components/ui/badge";

export function CCPerformance() {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState("");
  const [displayValue, setDisplayValue] = useState(""); // what shows in the field
  const [companies, setCompanies] = useState([]);
  const [selectedCompany, setSelectedCompany] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const triggerRef = useRef(null);

  // -------- Helpers
  const formatNumber = (n) =>
    Number.isNaN(n)
      ? "Tidak ada data"
      : new Intl.NumberFormat("id-ID", { maximumFractionDigits: 0 }).format(n);

  const toNum = (x) => (x == null ? 0 : typeof x === "number" ? x : parseFloat(String(x)));

  // -------- Fetch + aggregate
  useEffect(() => {
    const fetchData = async () => {
      setLoading(true);
      setError(null);
      try {
        const [
          { data: dpsData, error: dpsError },
          { data: dssData, error: dssError },
          { data: dgsData, error: dgsError },
        ] = await Promise.all([
          supabase.from("v_dps_revenue").select("*"),
          supabase.from("v_dss_revenue").select("*"),
          supabase.from("v_dgs_revenue").select("*"),
        ]);
        if (dpsError || dssError || dgsError) throw (dpsError || dssError || dgsError);

        const rows = [
          ...(dpsData || []).map((r) => ({
            STANDARD_NAME: r.STANDARD_NAME,
            WITEL: r.WITEL,
            revenue: Number(r.revenue_bill || 0),
          })),
          ...(dssData || []).map((r) => ({
            STANDARD_NAME: r.STANDARD_NAME,
            WITEL: r.WITEL,
            revenue: Number(r.revenue_sold || 0),
          })),
          ...(dgsData || []).map((r) => ({
            STANDARD_NAME: r.STANDARD_NAME,
            WITEL: r.WITEL,
            revenue: Number(r.revenue_sold || 0),
          })),
        ];

        const byCompany = new Map();
        for (const r of rows) {
          const key = r.STANDARD_NAME;
          const prev = byCompany.get(key);
          if (!prev) {
            byCompany.set(key, {
              STANDARD_NAME: r.STANDARD_NAME,
              WITEL: r.WITEL,
              revenue: r.revenue,
              target: Number.NaN,
              achievementPct: Number.NaN,
            });
          } else {
            prev.revenue += r.revenue;
          }
        }

        // Join targets (keep NaN when missing)
        const { data: targetData, error: targetError } = await supabase
          .from("target_data")
          .select("STANDARD_NAME,TARGET");
        if (targetError) throw targetError;

        const targetMap = new Map(
          (targetData ?? []).map((t) => [t.STANDARD_NAME, Number(t.TARGET)])
        );

        for (const [key, comp] of byCompany.entries()) {
          const t = targetMap.get(key);
          let target = Number.NaN;
          let achievementPct = Number.NaN;
          if (t !== undefined && Number.isFinite(t)) {
            target = t;
            achievementPct = t > 0 ? (comp.revenue / t) * 100 : Number.NaN;
          }
          byCompany.set(key, { ...comp, target, achievementPct });
        }

        setCompanies(
          Array.from(byCompany.values()).sort((a, b) =>
            a.STANDARD_NAME.localeCompare(b.STANDARD_NAME)
          )
        );
      } catch (e) {
        console.error(e);
        setError(e?.message || "Failed to fetch data");
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  const companyNames = useMemo(() => companies.map((c) => c.STANDARD_NAME), [companies]);

  const filteredNames = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return companyNames;
    return companyNames.filter((n) => n.toLowerCase().includes(q));
  }, [companyNames, query]);

  const selectCompanyByName = (name) => {
    const comp =
      companies.find((c) => c.STANDARD_NAME.toLowerCase() === name.toLowerCase()) || null;
    setSelectedCompany(comp);
    setDisplayValue(name);
    setQuery("");
    setOpen(false);
  };

  const clearSelection = () => {
    setSelectedCompany(null);
    setDisplayValue("");
    setQuery("");
    setOpen(true);
    setTimeout(() => triggerRef.current?.focus(), 0);
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-lg font-bold text-gray-900">CC Performance</CardTitle>
      </CardHeader>

      <CardContent className="space-y-4">
        {/* Single combobox control */}
        <div className="space-y-1.5">
          <label className="text-sm font-medium">Search or select a company</label>

          <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
              <button
                ref={triggerRef}
                type="button"
                onClick={() => setOpen(true)}
                className="w-full inline-flex items-center justify-between rounded-md border bg-white px-3 py-2 text-left text-sm focus:outline-none focus:ring-2 focus:ring-offset-2"
                aria-expanded={open}
              >
                <span className={displayValue ? "text-gray-900" : "text-gray-500"}>
                  {displayValue || "Type to search companies…"}
                </span>
                {displayValue && (
                  <span
                    onClick={(e) => {
                      e.stopPropagation();
                      clearSelection();
                    }}
                    className="ml-2 inline-flex h-5 w-5 items-center justify-center rounded hover:bg-gray-100"
                    aria-label="Clear"
                    title="Clear"
                  >
                    <X className="h-4 w-4" />
                  </span>
                )}
              </button>
            </PopoverTrigger>

            <PopoverContent className="p-0 w-[min(560px,90vw)]">
              <Command shouldFilter={false}>
                <CommandInput
                  placeholder="Search companies…"
                  value={query}
                  onValueChange={setQuery}
                  onFocus={() => setOpen(true)}
                />
                <CommandList>
                  {loading ? (
                    <div className="py-6 text-center text-sm text-muted-foreground">
                      Loading companies…
                    </div>
                  ) : error ? (
                    <div className="py-6 text-center text-sm text-red-600">{error}</div>
                  ) : (
                    <>
                      <CommandEmpty>No companies found.</CommandEmpty>
                      <CommandGroup heading="Companies">
                        {filteredNames.map((name) => (
                          <CommandItem
                            key={name}
                            value={name}
                            onSelect={() => selectCompanyByName(name)}
                          >
                            {name}
                          </CommandItem>
                        ))}
                      </CommandGroup>
                    </>
                  )}
                </CommandList>
              </Command>
            </PopoverContent>
          </Popover>
        </div>

        {/* Instructions / hint */}
        {!selectedCompany && (
          <p className="text-sm text-muted-foreground">
            {loading ? "Loading data…" : error ? "Data load failed." : "Select a company to view details."}
          </p>
        )}

        {/* Detail panel — progress style */}
        {selectedCompany && (
          <>
            <Separator />
            {(() => {
              const pct = selectedCompany.achievementPct; // may be NaN
              const pctCapped = Number.isNaN(pct) ? 0 : Math.min(100, Math.max(0, pct));
              const status =
                Number.isNaN(pct) ? "unknown" : pct < 80 ? "poor" : pct < 100 ? "warn" : "good";

              const colors =
                {
                  poor: { dot: "bg-red-500", bar: "bg-red-600", badge: "bg-red-600 text-white" },
                  warn: { dot: "bg-amber-500", bar: "bg-amber-600", badge: "bg-amber-600 text-white" },
                  good: { dot: "bg-green-500", bar: "bg-green-600", badge: "bg-green-600 text-white" },
                  unknown: { dot: "bg-gray-400", bar: "bg-gray-300", badge: "bg-gray-400 text-white" },
                }[status] || {
                  dot: "bg-gray-400",
                  bar: "bg-gray-300",
                  badge: "bg-gray-400 text-white",
                };

              const revenueStr = formatIDRCompact(selectedCompany.revenue);
              const targetStr = formatIDRCompact(selectedCompany.target);

              return (
                <div className="rounded-xl border bg-white p-4">
                  <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-2">
                      <span className={`h-2.5 w-2.5 rounded-full ${colors.dot}`} />
                      <span className="font-semibold tracking-tight">
                        {selectedCompany.STANDARD_NAME}
                      </span>
                      <Badge className={colors.badge}>
                        {Number.isNaN(pct) ? "Tidak ada data target" : `${pct.toFixed(1)}%`}
                      </Badge>
                    </div>
                    <div className="text-right">
                      <div className="text-sm font-semibold">{revenueStr}</div>
                      <div className="text-xs text-muted-foreground">Target: {targetStr}</div>
                    </div>
                  </div>

                  <div className="mt-3 h-3 w-full rounded-full bg-gray-200 overflow-hidden">
                    <div
                      className={`h-full ${colors.bar} transition-all`}
                      style={{ width: `${pctCapped}%` }}
                    />
                  </div>
                </div>
              );
            })()}
          </>
        )}
      </CardContent>
    </Card>
  );
}

export default CCPerformance;
