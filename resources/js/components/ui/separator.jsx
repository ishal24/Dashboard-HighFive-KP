import * as React from "react";

export function Separator({ className = "", orientation = "horizontal", decorative = true, ...props }) {
  const isVertical = orientation === "vertical";
  return (
    <div
      role={decorative ? "none" : "separator"}
      aria-orientation={isVertical ? "vertical" : "horizontal"}
      className={`${isVertical ? "w-px h-full" : "h-px w-full"} bg-gray-200 shrink-0 ${className}`}
      {...props}
    />
  );
}
