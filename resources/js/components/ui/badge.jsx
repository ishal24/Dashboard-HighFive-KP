// resources/js/components/ui/badge.jsx
import React from "react";

export function Badge({ variant = "default", className = "", children }) {
  const base = "inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border";
  const styles =
    variant === "destructive"
      ? "bg-red-50 text-red-700 border-red-200"
      : variant === "secondary"
      ? "bg-gray-100 text-gray-700 border-gray-200"
      : "bg-blue-50 text-blue-700 border-blue-200";

  return <span className={`${base} ${styles} ${className}`}>{children}</span>;
}
