// resources/js/components/ui/progress.jsx
import React from "react";

export function Progress({ value = 0, className = "", barClassName = "" }) {
  const v = Math.max(0, Math.min(100, Number(value) || 0));
  return (
    <div className={`relative w-full rounded-full bg-gray-200 ${className}`}>
      <div
        className={`absolute left-0 top-0 h-full rounded-full ${barClassName}`}
        style={{ width: `${v}%` }}
      />
    </div>
  );
}
