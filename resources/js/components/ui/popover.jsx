import React, { createContext, useContext, useEffect, useLayoutEffect, useMemo, useRef, useState } from "react";

const PopoverContext = createContext(null);

export function Popover({ open: controlledOpen, onOpenChange, children }) {
  const [uncontrolledOpen, setUncontrolledOpen] = useState(false);
  const open = controlledOpen != null ? controlledOpen : uncontrolledOpen;
  const setOpen = (v) => (onOpenChange ? onOpenChange(v) : setUncontrolledOpen(v));
  const triggerRef = useRef(null);

  const value = useMemo(() => ({ open, setOpen, triggerRef }), [open]);

  return <PopoverContext.Provider value={value}>{children}</PopoverContext.Provider>;
}

export function PopoverTrigger({ asChild, children }) {
  const ctx = useContext(PopoverContext);
  if (!ctx) throw new Error("PopoverTrigger must be used within <Popover>");
  const { open, setOpen, triggerRef } = ctx;

  const attachRef = (node) => {
    triggerRef.current = node;
    // forward child ref if it exists
    const childRef = children && children.ref;
    if (typeof childRef === "function") childRef(node);
    else if (childRef && typeof childRef === "object") childRef.current = node;
  };

  const commonProps = {
    ref: attachRef,
    "aria-expanded": !!open,
    "aria-haspopup": "dialog",
    onClick: (e) => {
      if (children && children.props && typeof children.props.onClick === "function") {
        children.props.onClick(e);
      }
      setOpen(!open);
    },
  };

  if (asChild && React.isValidElement(children)) {
    return React.cloneElement(children, commonProps);
  }

  return (
    <button
      type="button"
      {...commonProps}
      className="inline-flex items-center justify-center rounded-md border bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-offset-2"
    >
      {children}
    </button>
  );
}

export function PopoverContent({
  className = "",
  align = "start",          // "start" | "center" | "end"
  sideOffset = 8,
  style,
  children,
  ...rest
}) {
  const ctx = useContext(PopoverContext);
  if (!ctx) throw new Error("PopoverContent must be used within <Popover>");
  const { open, setOpen, triggerRef } = ctx;

  const panelRef = useRef(null);
  const [pos, setPos] = useState({ top: 0, left: 0 });

  const computePosition = () => {
    const trg = triggerRef.current;
    const pnl = panelRef.current;
    if (!trg || !pnl) return;

    const r = trg.getBoundingClientRect();
    const top = Math.round(r.bottom + sideOffset + window.scrollY);

    // default left aligns with trigger left
    let left = Math.round(r.left + window.scrollX);
    if (align === "center") {
      left = Math.round(r.left + r.width / 2 - pnl.offsetWidth / 2 + window.scrollX);
    } else if (align === "end") {
      left = Math.round(r.right - pnl.offsetWidth + window.scrollX);
    }

    setPos({ top, left });
  };

  useLayoutEffect(() => {
    if (!open) return;
    computePosition();
    const onScroll = () => computePosition();
    const onResize = () => computePosition();
    window.addEventListener("scroll", onScroll, true);
    window.addEventListener("resize", onResize);
    return () => {
      window.removeEventListener("scroll", onScroll, true);
      window.removeEventListener("resize", onResize);
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open, align, sideOffset]);

  // Close on outside click and Escape
  useEffect(() => {
    if (!open) return;
    const onDown = (e) => {
      const pnl = panelRef.current;
      const trg = triggerRef.current;
      if (!pnl || !trg) return;
      if (pnl.contains(e.target) || trg.contains(e.target)) return;
      setOpen(false);
    };
    const onKey = (e) => {
      if (e.key === "Escape") setOpen(false);
    };
    document.addEventListener("mousedown", onDown);
    document.addEventListener("keydown", onKey);
    return () => {
      document.removeEventListener("mousedown", onDown);
      document.removeEventListener("keydown", onKey);
    };
  }, [open, setOpen, triggerRef]);

  if (!open) return null;

  return (
    <div
      ref={panelRef}
      role="dialog"
      style={{ position: "absolute", top: pos.top, left: pos.left, ...style }}
      className={
        "z-50 min-w-[12rem] rounded-md border bg-white p-2 shadow-md outline-none " +
        "animate-in fade-in slide-in-from-top-2 " +
        className
      }
      {...rest}
    >
      {children}
    </div>
  );
}
