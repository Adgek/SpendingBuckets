import React, { useEffect, useState } from 'react';
interface ProgressBarProps {
  current: number;
  target: number;
  isComplete: boolean;
}
export function ProgressBar({ current, target, isComplete }: ProgressBarProps) {
  const [width, setWidth] = useState(0);
  const percentage =
  target > 0 ? Math.min(100, Math.max(0, current / target * 100)) : 0;
  useEffect(() => {
    // Animate on mount
    const timer = setTimeout(() => setWidth(percentage), 100);
    return () => clearTimeout(timer);
  }, [percentage]);
  return (
    <div className="relative w-full h-1.5 bg-navy rounded-full overflow-hidden shadow-inner">
      {/* Antique Ruler Tick Marks */}
      <div className="absolute inset-0 pointer-events-none z-10">
        <div className="absolute left-1/4 top-0 bottom-0 w-[1px] bg-charcoal/80" />
        <div className="absolute left-2/4 top-0 bottom-0 w-[1px] bg-charcoal/80" />
        <div className="absolute left-3/4 top-0 bottom-0 w-[1px] bg-charcoal/80" />
      </div>

      {/* Fill */}
      <div
        className={`absolute top-0 left-0 h-full rounded-full transition-all duration-1000 ease-out ${isComplete ? 'bg-forest shadow-[0_0_10px_rgba(74,120,86,0.4)]' : 'bg-gold shadow-[0_0_10px_rgba(197,160,89,0.4)]'}`}
        style={{
          width: `${width}%`
        }} />

    </div>);

}