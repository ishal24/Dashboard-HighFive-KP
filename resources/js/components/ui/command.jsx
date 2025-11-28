import * as React from "react";
import { Command as CommandPrimitive } from "cmdk";

/** tiny class join helper */
function cn(...args) {
  return args.filter(Boolean).join(" ");
}

/**
 * Root
 */
export const Command = React.forwardRef(function Command(
  { className, ...props },
  ref
) {
  return (
    <CommandPrimitive
      ref={ref}
      className={cn(
        "flex w-full flex-col overflow-hidden rounded-md border border-gray-200 bg-white text-gray-900 shadow-sm",
        className
      )}
      {...props}
    />
  );
});

/**
 * Input
 */
export const CommandInput = React.forwardRef(function CommandInput(
  { className, ...props },
  ref
) {
  return (
    <div className="flex items-center border-b border-gray-200 px-3">
      <CommandPrimitive.Input
        ref={ref}
        className={cn(
          "h-10 w-full bg-transparent py-2 text-sm outline-none placeholder:text-gray-400",
          className
        )}
        {...props}
      />
    </div>
  );
});

/**
 * List (scrollable results container)
 */
export const CommandList = React.forwardRef(function CommandList(
  { className, ...props },
  ref
) {
  return (
    <CommandPrimitive.List
      ref={ref}
      className={cn("max-h-64 overflow-y-auto p-1", className)}
      {...props}
    />
  );
});

/**
 * Empty state
 */
export const CommandEmpty = React.forwardRef(function CommandEmpty(
  { className, ...props },
  ref
) {
  return (
    <div
      ref={ref}
      className={cn(
        "py-6 text-center text-sm text-gray-500",
        className
      )}
      {...props}
    />
  );
});

/**
 * Group
 */
export const CommandGroup = React.forwardRef(function CommandGroup(
  { className, heading, children, ...props },
  ref
) {
  return (
    <CommandPrimitive.Group
      ref={ref}
      heading={heading}
      className={cn("pb-2 pt-1", className)}
      {...props}
    >
      {heading ? (
        <div className="px-2 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">
          {heading}
        </div>
      ) : null}
      {children}
    </CommandPrimitive.Group>
  );
});

/**
 * Separator
 */
export const CommandSeparator = React.forwardRef(function CommandSeparator(
  { className, ...props },
  ref
) {
  return (
    <CommandPrimitive.Separator
      ref={ref}
      className={cn("my-1 h-px w-full bg-gray-200", className)}
      {...props}
    />
  );
});

/**
 * Item
 */
export const CommandItem = React.forwardRef(function CommandItem(
  { className, ...props },
  ref
) {
  return (
    <CommandPrimitive.Item
      ref={ref}
      className={cn(
        "relative flex cursor-pointer select-none items-center gap-2 rounded-md px-2 py-2 text-sm outline-none",
        "data-[disabled=true]:pointer-events-none data-[disabled=true]:opacity-50",
        "data-[selected=true]:bg-gray-100",
        "hover:bg-gray-100",
        className
      )}
      {...props}
    />
  );
});

/**
 * Shortcut (right-aligned helper text)
 */
export function CommandShortcut({ className, ...props }) {
  return (
    <span
      className={cn("ml-auto text-xs tracking-widest text-gray-400", className)}
      {...props}
    />
  );
}
