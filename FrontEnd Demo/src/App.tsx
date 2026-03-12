import React, { useState } from 'react';
import { Sidebar } from './components/Sidebar';
import { BucketStack } from './components/BucketStack';
import { ActionPane } from './components/ActionPane';
import { initialBuckets } from './data/mockBuckets';
import { Bucket } from './types/bucket';
export function App() {
  const [buckets, setBuckets] = useState<Bucket[]>(initialBuckets);
  return (
    <div className="flex h-screen w-full bg-charcoal overflow-hidden font-sans text-warm-white selection:bg-gold/30">
      <Sidebar />
      <main className="flex-1 h-full relative z-0">
        <BucketStack buckets={buckets} setBuckets={setBuckets} />
      </main>
      <ActionPane buckets={buckets} />
    </div>);

}