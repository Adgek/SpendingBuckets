import React, { useState } from 'react';
import { Bucket } from '../types/bucket';
import { BucketCard } from './BucketCard';
interface BucketStackProps {
  buckets: Bucket[];
  setBuckets: (buckets: Bucket[]) => void;
}
export function BucketStack({ buckets, setBuckets }: BucketStackProps) {
  const [draggedIndex, setDraggedIndex] = useState<number | null>(null);
  const [dragOverIndex, setDragOverIndex] = useState<number | null>(null);
  const handleDragStart = (e: React.DragEvent, index: number) => {
    setDraggedIndex(index);
    e.dataTransfer.effectAllowed = 'move';
    // Required for Firefox
    if (e.dataTransfer) {
      e.dataTransfer.setData('text/plain', index.toString());
    }
  };
  const handleDragOver = (e: React.DragEvent, index: number) => {
    e.preventDefault();
    if (draggedIndex === index) return;
    setDragOverIndex(index);
  };
  const handleDrop = (e: React.DragEvent, index: number) => {
    e.preventDefault();
    if (draggedIndex === null || draggedIndex === index) {
      setDragOverIndex(null);
      return;
    }
    const newBuckets = [...buckets];
    const [removed] = newBuckets.splice(draggedIndex, 1);
    newBuckets.splice(index, 0, removed);
    // Update priorities
    const updatedBuckets = newBuckets.map((b, i) => ({
      ...b,
      priority: i + 1
    }));
    setBuckets(updatedBuckets);
    setDraggedIndex(null);
    setDragOverIndex(null);
  };
  const handleDragEnd = () => {
    setDraggedIndex(null);
    setDragOverIndex(null);
  };
  return (
    <div className="flex-1 h-full flex flex-col max-w-4xl mx-auto w-full px-8 py-10">
      {/* Header */}
      <div className="mb-8">
        <h1 className="font-serif text-4xl text-warm-white mb-2 tracking-tight">
          The Stack
        </h1>
        <p className="text-muted text-sm uppercase tracking-widest font-medium">
          Priority Waterfall
        </p>
      </div>

      {/* Bucket List */}
      <div className="flex-1 overflow-y-auto pr-4 pb-20 space-y-2 -mr-4">
        {buckets.map((bucket, index) =>
        <BucketCard
          key={bucket.id}
          bucket={bucket}
          index={index}
          isDragging={draggedIndex === index}
          isDragOver={dragOverIndex === index}
          onDragStart={handleDragStart}
          onDragOver={handleDragOver}
          onDrop={handleDrop}
          onDragEnd={handleDragEnd} />

        )}
      </div>
    </div>);

}