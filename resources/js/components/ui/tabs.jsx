// resources/js/components/ui/tabs.jsx
import React, { createContext, useContext, useMemo, useState } from "react";

const TabsCtx = createContext(null);

export function Tabs({ value, onValueChange, defaultValue, className = "", children }) {
  const [internal, setInternal] = useState(defaultValue ?? "");
  const controlled = value !== undefined;
  const current = controlled ? value : internal;

  const ctx = useMemo(
    () => ({
      value: current,
      setValue: (v) => (controlled ? onValueChange?.(v) : setInternal(v)),
    }),
    [controlled, current, onValueChange]
  );

  return (
    <TabsCtx.Provider value={ctx}>
      <div className={className} role="tablist">{children}</div>
    </TabsCtx.Provider>
  );
}

export function TabsList({ className = "", children }) {
  return <div className={`inline-grid gap-2 rounded-md bg-gray-100 p-1 ${className}`}>{children}</div>;
}

export function TabsTrigger({ value, className = "", children }) {
  const ctx = useContext(TabsCtx);
  const active = ctx?.value === value;
  return (
    <button
      type="button"
      role="tab"
      aria-selected={active}
      onClick={() => ctx?.setValue(value)}
      className={`px-3 py-1.5 text-sm font-medium rounded-md transition
        ${active ? "bg-white shadow text-gray-900" : "text-gray-600 hover:text-gray-900" }
        ${className}`}
    >
      {children}
    </button>
  );
}

export function TabsContent({ value, className = "", children }) {
  const ctx = useContext(TabsCtx);
  if (ctx?.value !== value) return null;
  return <div className={className}>{children}</div>;
}
