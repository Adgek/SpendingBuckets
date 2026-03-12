import React from 'react';
import { GripVerticalIcon, CheckCircle2Icon } from 'lucide-react';
import { motion } from 'framer-motion';
import { Bucket } from '../types/bucket';
import { ProgressBar } from './ProgressBar';
interface BucketCardProps {
  bucket: Bucket;
  index: number;
  isDragging: boolean;
  isDragOver: boolean;
  onDragStart: (e: React.DragEvent, index: number) => void;
  onDragOver: (e: React.DragEvent, index: number) => void;
  onDrop: (e: React.DragEvent, index: number) => void;
  onDragEnd: () => void;
}
export function BucketCard({
  bucket,
  index,
  isDragging,
  isDragOver,
  onDragStart,
  onDragOver,
  onDrop,
  onDragEnd
}: BucketCardProps) {
  const isComplete = bucket.current >= bucket.target;
  const percentage =
  bucket.target > 0 ? Math.floor(bucket.current / bucket.target * 100) : 0;
  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
      maximumFractionDigits: 0
    }).format(amount);
  };
  return (
    <motion.div
      layout
      initial={{
        opacity: 0,
        y: 20
      }}
      animate={{
        opacity: 1,
        y: 0
      }}
      transition={{
        duration: 0.4,
        delay: index * 0.05
      }}
      draggable
      onDragStart={(e: any) => onDragStart(e, index)}
      onDragOver={(e: any) => onDragOver(e, index)}
      onDrop={(e: any) => onDrop(e, index)}
      onDragEnd={onDragEnd}
      className={`
        relative flex items-center p-4 bg-elevated rounded-lg border 
        transition-all duration-200 group
        ${isDragging ? 'opacity-50 scale-[1.02] shadow-2xl border-gold/50 z-50' : 'opacity-100 border-border shadow-sm hover:border-surface'}
        ${isDragOver ? 'border-t-2 border-t-gold mt-2' : ''}
      `}>

      {/* Drag Handle */}
      <div className="mr-4 text-muted cursor-grab active:cursor-grabbing opacity-40 group-hover:opacity-100 transition-opacity">
        <GripVerticalIcon size={20} />
      </div>

      {/* Priority Badge */}
      <div
        className={`
        flex-shrink-0 w-7 h-7 rounded-full border flex items-center justify-center text-xs font-medium mr-4
        ${isComplete ? 'border-forest text-forest bg-forest/10' : 'border-gold text-gold bg-gold/10'}
      `}>

        {bucket.priority}
      </div>

      {/* Main Content */}
      <div className="flex-1 min-w-0">
        <div className="flex justify-between items-end mb-2">
          <div className="flex items-center space-x-2">
            <h3 className="font-serif text-lg text-warm-white truncate">
              {bucket.name}
            </h3>
            {isComplete &&
            <CheckCircle2Icon size={16} className="text-forest" />
            }
          </div>
          <div className="text-right">
            <span
              className={`font-serif text-lg ${isComplete ? 'text-forest' : 'text-gold'}`}>

              {formatCurrency(bucket.current)}
            </span>
            <span className="font-serif text-muted text-sm ml-1">
              / {formatCurrency(bucket.target)}
            </span>
          </div>
        </div>

        <div className="flex items-center space-x-3">
          <div className="flex-1">
            <ProgressBar
              current={bucket.current}
              target={bucket.target}
              isComplete={isComplete} />

          </div>
          <span className="text-xs text-muted font-medium w-9 text-right">
            {percentage}%
          </span>
        </div>
      </div>
    </motion.div>);

}