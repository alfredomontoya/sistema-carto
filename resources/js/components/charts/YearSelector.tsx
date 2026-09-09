'use client';

import { ChevronDown, ChevronUp } from 'lucide-react';
import { useState, useRef, useEffect } from 'react';
import { cn } from '@/lib/utils';

interface YearSelectorProps {
  value: number;
  options: number[];
  onChange?: (year: number) => void;
}

export function YearSelector({ value, options, onChange }: YearSelectorProps) {
  const [isOpen, setIsOpen] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);
  const buttonRef = useRef<HTMLButtonElement>(null);

  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node) &&
          buttonRef.current && !buttonRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    }

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleSelect = (year: number) => {
    setIsOpen(false);
    onChange?.(year);
  };

  return (
    <div className="relative" ref={dropdownRef}>
      <button
        ref={buttonRef}
        type="button"
        onClick={() => setIsOpen(!isOpen)}
        className={cn(
          'flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-foreground bg-background border border-input rounded-md',
          'hover:bg-accent hover:text-accent-foreground transition-colors',
          'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2'
        )}
        aria-expanded={isOpen}
        aria-haspopup="listbox"
      >
        <span className="min-w-[4.5rem] text-right">{value}</span>
        {isOpen ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
      </button>

      {isOpen && (
        <div className="absolute right-0 z-50 mt-1 w-24 origin-top-right rounded-md bg-popover border border-input shadow-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 animate-in fade-in-0 zoom-in-95 duration-100">
          <div className="p-1">
            {options.map((year) => (
              <button
                key={year}
                type="button"
                onClick={() => handleSelect(year)}
                className={cn(
                  'flex w-full items-center px-2 py-1.5 text-sm rounded',
                  'hover:bg-accent hover:text-accent-foreground',
                  year === value && 'bg-accent text-accent-foreground font-medium'
                )}
                role="option"
                aria-selected={year === value}
              >
                {year}
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}