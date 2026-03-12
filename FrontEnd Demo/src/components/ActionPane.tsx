import React, { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { AlertTriangleIcon, ArrowRightIcon } from 'lucide-react';
import { TabType, Bucket } from '../types/bucket';
interface ActionPaneProps {
  buckets: Bucket[];
}
export function ActionPane({ buckets }: ActionPaneProps) {
  const [activeTab, setActiveTab] = useState<TabType>('deposit');
  const tabs: TabType[] = ['deposit', 'expense', 'transfer'];
  const isDangerMode = activeTab === 'transfer';
  return (
    <aside
      className={`
      w-[380px] h-full flex flex-col flex-shrink-0 transition-colors duration-500 relative z-10
      ${isDangerMode ? 'bg-[#1A1010] border-l-2 border-crimson' : 'bg-navy border-l border-border'}
    `}>

      {/* Danger Mode Overlay Tint */}
      {isDangerMode &&
      <div className="absolute inset-0 bg-crimson/5 pointer-events-none" />
      }

      <div className="p-8 flex-1 flex flex-col relative z-10">
        <header className="mb-8">
          <h2 className="font-serif text-2xl text-warm-white mb-6">
            The Ledger Desk
          </h2>

          {/* Tabs */}
          <div className="flex bg-charcoal rounded-full p-1 border border-border/50">
            {tabs.map((tab) =>
            <button
              key={tab}
              onClick={() => setActiveTab(tab)}
              className="relative flex-1 py-2 text-sm font-medium capitalize z-10">

                {activeTab === tab &&
              <motion.div
                layoutId="activeTab"
                className={`absolute inset-0 rounded-full -z-10 ${isDangerMode ? 'bg-crimson' : 'bg-gold'}`}
                transition={{
                  type: 'spring',
                  stiffness: 300,
                  damping: 30
                }} />

              }
                <span
                className={`transition-colors ${activeTab === tab ? 'text-charcoal' : 'text-muted hover:text-warm-white'}`}>

                  {tab}
                </span>
              </button>
            )}
          </div>
        </header>

        {/* Tab Content */}
        <div className="flex-1 relative">
          <AnimatePresence mode="wait">
            <motion.div
              key={activeTab}
              initial={{
                opacity: 0,
                y: 10
              }}
              animate={{
                opacity: 1,
                y: 0
              }}
              exit={{
                opacity: 0,
                y: -10
              }}
              transition={{
                duration: 0.2
              }}
              className="absolute inset-0">

              {activeTab === 'deposit' && <DepositForm buckets={buckets} />}
              {activeTab === 'expense' && <ExpenseForm buckets={buckets} />}
              {activeTab === 'transfer' && <TransferForm buckets={buckets} />}
            </motion.div>
          </AnimatePresence>
        </div>
      </div>
    </aside>);

}
function DepositForm({ buckets }: {buckets: Bucket[];}) {
  return (
    <div className="space-y-6">
      <div>
        <label className="block text-xs text-muted uppercase tracking-wider mb-2">
          Deposit Amount
        </label>
        <div className="relative">
          <span className="absolute left-4 top-1/2 -translate-y-1/2 font-serif text-2xl text-gold">
            $
          </span>
          <input
            type="number"
            placeholder="0.00"
            className="w-full bg-surface border border-border rounded-lg py-4 pl-10 pr-4 font-serif text-2xl text-warm-white focus:outline-none focus:border-gold transition-colors" />

        </div>
      </div>

      <button className="w-full bg-gold text-charcoal font-medium py-3 rounded-lg hover:bg-gold/90 transition-colors flex items-center justify-center space-x-2">
        <span>Fund Next in Stack</span>
        <ArrowRightIcon size={16} />
      </button>
    </div>);

}
function ExpenseForm({ buckets }: {buckets: Bucket[];}) {
  return (
    <div className="space-y-5">
      <div>
        <label className="block text-xs text-muted uppercase tracking-wider mb-2">
          Select Bucket
        </label>
        <select className="w-full bg-surface border border-border rounded-lg p-3 text-warm-white focus:outline-none focus:border-gold transition-colors appearance-none">
          <option value="">Choose a category...</option>
          {buckets.map((b) =>
          <option key={b.id} value={b.id}>
              {b.priority}. {b.name} (${b.current} available)
            </option>
          )}
        </select>
      </div>

      <div>
        <label className="block text-xs text-muted uppercase tracking-wider mb-2">
          Amount
        </label>
        <div className="relative">
          <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted">
            $
          </span>
          <input
            type="number"
            placeholder="0.00"
            className="w-full bg-surface border border-border rounded-lg p-3 pl-8 text-warm-white focus:outline-none focus:border-gold transition-colors" />

        </div>
      </div>

      <div>
        <label className="block text-xs text-muted uppercase tracking-wider mb-2">
          Description
        </label>
        <input
          type="text"
          placeholder="e.g. Whole Foods"
          className="w-full bg-surface border border-border rounded-lg p-3 text-warm-white focus:outline-none focus:border-gold transition-colors" />

      </div>

      <button className="w-full bg-surface border border-border text-warm-white font-medium py-3 rounded-lg hover:border-gold hover:text-gold transition-colors mt-4">
        Record Expense
      </button>
    </div>);

}
function TransferForm({ buckets }: {buckets: Bucket[];}) {
  return (
    <div className="space-y-5">
      <div className="flex items-center space-x-2 text-crimson bg-crimson/10 p-3 rounded-lg border border-crimson/20 mb-6">
        <AlertTriangleIcon size={18} />
        <span className="text-xs font-semibold uppercase tracking-wider">
          Restricted Action
        </span>
      </div>

      <p className="text-sm text-muted mb-4">
        Transfers break the priority waterfall sequence. Use with caution.
      </p>

      <div>
        <label className="block text-xs text-muted uppercase tracking-wider mb-2">
          From Bucket
        </label>
        <select className="w-full bg-surface border border-crimson/30 rounded-lg p-3 text-warm-white focus:outline-none focus:border-crimson transition-colors appearance-none">
          <option value="">Select source...</option>
          {buckets.
          filter((b) => b.current > 0).
          map((b) =>
          <option key={b.id} value={b.id}>
                {b.name} (${b.current} available)
              </option>
          )}
        </select>
      </div>

      <div>
        <label className="block text-xs text-muted uppercase tracking-wider mb-2">
          To Bucket
        </label>
        <select className="w-full bg-surface border border-crimson/30 rounded-lg p-3 text-warm-white focus:outline-none focus:border-crimson transition-colors appearance-none">
          <option value="">Select destination...</option>
          {buckets.map((b) =>
          <option key={b.id} value={b.id}>
              {b.name}
            </option>
          )}
        </select>
      </div>

      <div>
        <label className="block text-xs text-muted uppercase tracking-wider mb-2">
          Amount
        </label>
        <div className="relative">
          <span className="absolute left-3 top-1/2 -translate-y-1/2 text-crimson/70">
            $
          </span>
          <input
            type="number"
            placeholder="0.00"
            className="w-full bg-surface border border-crimson/30 rounded-lg p-3 pl-8 text-warm-white focus:outline-none focus:border-crimson transition-colors" />

        </div>
      </div>

      <button className="w-full bg-crimson text-warm-white font-medium py-3 rounded-lg hover:bg-crimson/90 transition-colors mt-4 shadow-[0_0_15px_rgba(139,0,0,0.3)]">
        Execute Transfer
      </button>
    </div>);

}