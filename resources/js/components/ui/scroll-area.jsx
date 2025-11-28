import React from "react";
export function ScrollArea({ className = "", children, ...props }) {
  return (
    <div className={`relative overflow-hidden ${className}`} {...props}>
      <div className="h-full w-full overflow-y-auto pr-2 custom-scrollbar">
        {children}
      </div>
    </div>
  );
}
