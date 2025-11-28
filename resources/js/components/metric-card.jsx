import '../../../public/css/inertia.css'
import React from "react";

export function MetricCard({
  title,
  value,
  percentage,          // optional number
  target,              // string
  targetType,          // 'positive' | 'negative' | 'NaN'
  ytd,                 // string like '12.3%'
  icon: Icon,          // lucide icon
  bgClass = "bg-gray-50",
  accentTextClass = "text-gray-800",
  iconBgClass = "bg-gray-700",
}) {
  const pctText =
    typeof percentage === "number" && !Number.isNaN(percentage)
      ? `${Math.round(percentage)}%`
      : null;

  const ytdClass =
    targetType === "negative" ? "text-red-600" : targetType === "positive" ? "text-green-600" : "text-gray-500";

  return (
    <div className={`border rounded-xl p-4 ${bgClass}`}>
      <div className="flex items-center justify-between">
        <div className={`w-10 h-10 rounded-lg flex items-center justify-center ${iconBgClass}`}>
          {Icon ? <Icon className="h-5 w-5 text-white" /> : null}
        </div>
        {pctText && <span className="text-xs text-gray-500">{pctText}</span>}
      </div>

      <div className="mt-3">
        <div className="text-sm text-gray-500">{title}</div>
        <div className={`text-2xl font-semibold ${accentTextClass}`}>{value}</div>
      </div>

      <div className="mt-2 text-xs text-gray-600 flex items-center justify-between">
        <span>Target: {target}</span>
        {ytd ? <span className={ytdClass}>{ytd}</span> : null}
      </div>
    </div>
  );
}
