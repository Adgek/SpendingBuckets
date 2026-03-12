import React from 'react';
import { LayersIcon, SettingsIcon } from 'lucide-react';
export function Sidebar() {
  return (
    <aside className="w-16 h-full bg-navy border-r border-border flex flex-col items-center py-6 flex-shrink-0 z-20 relative">
      {/* Logo */}
      <div className="w-10 h-10 rounded-xl bg-elevated border border-gold/30 flex items-center justify-center mb-12 shadow-[0_0_15px_rgba(197,160,89,0.1)]">
        <span className="font-serif text-gold font-bold text-xl">S</span>
      </div>

      {/* Nav Items */}
      <nav className="flex-1 w-full flex flex-col items-center space-y-8">
        <NavItem icon={<LayersIcon size={22} />} active />
        <NavItem icon={<SettingsIcon size={22} />} />
      </nav>

      {/* User Avatar */}
      <div className="w-9 h-9 rounded-full bg-surface border border-border overflow-hidden mt-auto cursor-pointer hover:border-gold/50 transition-colors">
        <img
          src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
          alt="User avatar"
          className="w-full h-full object-cover opacity-80" />

      </div>
    </aside>);

}
function NavItem({
  icon,
  active = false



}: {icon: React.ReactNode;active?: boolean;}) {
  return (
    <button className="relative w-full flex justify-center group">
      {active &&
      <div className="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-gold rounded-r-full shadow-[0_0_10px_rgba(197,160,89,0.5)]" />
      }
      <div
        className={`p-2 rounded-lg transition-colors ${active ? 'text-gold bg-gold/10' : 'text-muted hover:text-warm-white hover:bg-surface'}`}>

        {icon}
      </div>
    </button>);

}