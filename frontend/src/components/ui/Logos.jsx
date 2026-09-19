import React from 'react';

/**
 * Official Crest / Emblem of Pemerintah Provinsi Sulawesi Utara (Sulut)
 */
export function LogoPemprovSulut({ className = "w-10 h-10", showLabel = false }) {
  return (
    <div className="inline-flex items-center gap-2">
      <svg
        className={`${className} shrink-0 drop-shadow-sm`}
        viewBox="0 0 100 120"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <defs>
          <linearGradient id="sulutShield" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stopColor="#1E3A8A" />
            <stop offset="100%" stopColor="#172554" />
          </linearGradient>
          <linearGradient id="goldRim" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stopColor="#FDE047" />
            <stop offset="100%" stopColor="#D97706" />
          </linearGradient>
        </defs>
        {/* Shield Frame */}
        <path
          d="M50 5 L92 22 C92 78 50 112 50 115 C50 112 8 78 8 22 Z"
          fill="url(#sulutShield)"
          stroke="url(#goldRim)"
          strokeWidth="3.5"
        />
        {/* Red & White Flag Arc */}
        <path d="M18 35 Q50 25 82 35 L82 45 Q50 35 18 45 Z" fill="#DC2626" />
        <path d="M18 45 Q50 35 82 45 L82 55 Q50 45 18 55 Z" fill="#FFFFFF" />
        {/* Mount Klabat & Coconut Tree Icon */}
        <path d="M30 80 L50 52 L70 80 Z" fill="#059669" opacity="0.9" />
        <circle cx="50" cy="46" r="5" fill="#F59E0B" />
        {/* Waves of North Sulawesi Sea */}
        <path d="M22 84 Q36 80 50 84 T78 84" stroke="#60A5FA" strokeWidth="2.5" strokeLinecap="round" />
        <path d="M25 90 Q37 87 50 90 T75 90" stroke="#93C5FD" strokeWidth="2" strokeLinecap="round" />
        {/* Star at the top */}
        <polygon points="50,12 52,18 58,18 53,22 55,28 50,24 45,28 47,22 42,18 48,18" fill="#FDE047" />
        {/* Ribbon at base with Sulut motto */}
        <path d="M20 102 Q50 98 80 102 L76 108 Q50 104 24 108 Z" fill="#FEF08A" />
      </svg>
      {showLabel && (
        <div className="flex flex-col text-left">
          <span className="text-[10px] uppercase tracking-wider text-blue-600 font-semibold leading-tight">Pemerintah Provinsi</span>
          <span className="text-xs font-bold text-slate-800 leading-tight">Sulawesi Utara</span>
        </div>
      )}
    </div>
  );
}

/**
 * Official Emblem of Biro Kesejahteraan Rakyat (Biro Kesra) Setda Prov. Sulut
 */
export function LogoBiroKesra({ className = "w-10 h-10", showLabel = false }) {
  return (
    <div className="inline-flex items-center gap-2">
      <svg
        className={`${className} shrink-0 drop-shadow-sm`}
        viewBox="0 0 100 100"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <defs>
          <linearGradient id="kesraGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stopColor="#0284C7" />
            <stop offset="100%" stopColor="#0369A1" />
          </linearGradient>
          <linearGradient id="kesraGold" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stopColor="#FBBF24" />
            <stop offset="100%" stopColor="#B45309" />
          </linearGradient>
        </defs>
        {/* Circular Outer Golden Laurel Ring */}
        <circle cx="50" cy="50" r="45" fill="white" stroke="url(#kesraGold)" strokeWidth="3" />
        <circle cx="50" cy="50" r="39" fill="url(#kesraGrad)" />
        {/* Caring Hands / Shield of Welfare */}
        <path
          d="M26 62 C28 48 42 38 50 38 C58 38 72 48 74 62 C68 72 56 76 50 76 C44 76 32 72 26 62 Z"
          fill="#FFFFFF"
          opacity="0.95"
        />
        {/* Heart / Human Welfare Core */}
        <circle cx="50" cy="50" r="6" fill="#F59E0B" />
        {/* Radiating Light Rays */}
        <path d="M50 20 L50 26 M30 28 L34 32 M70 28 L66 32" stroke="#FDE047" strokeWidth="2.5" strokeLinecap="round" />
        {/* Mini Ribbon Base */}
        <path d="M30 84 Q50 80 70 84 L67 89 Q50 86 33 89 Z" fill="#FDE047" />
      </svg>
      {showLabel && (
        <div className="flex flex-col text-left">
          <span className="text-[10px] uppercase tracking-wider text-sky-600 font-semibold leading-tight">Biro Kesra</span>
          <span className="text-xs font-bold text-slate-800 leading-tight">Setda Prov. Sulut</span>
        </div>
      )}
    </div>
  );
}

/**
 * SIKOMANDO Shield Logo (uses /logo.png with fallback SVG shield)
 */
export function LogoSikomando({ className = "w-10 h-10", showLabel = false }) {
  return (
    <div className="inline-flex items-center gap-2">
      <img
        src="/logo.png"
        alt="Logo SIKOMANDO"
        className={`${className} object-contain shrink-0`}
        onError={(e) => {
          // Fallback if image fails to load
          e.currentTarget.style.display = 'none';
          e.currentTarget.nextSibling.style.display = 'block';
        }}
      />
      {/* SVG Fallback */}
      <svg
        className={`${className} shrink-0 hidden`}
        viewBox="0 0 100 100"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <defs>
          <linearGradient id="sikomandoGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stopColor="#1D4ED8" />
            <stop offset="100%" stopColor="#0B1120" />
          </linearGradient>
        </defs>
        <path
          d="M50 6 L88 20 C88 68 50 94 50 94 C50 94 12 68 12 20 Z"
          fill="url(#sikomandoGrad)"
          stroke="#3B82F6"
          strokeWidth="3"
        />
        <path d="M34 50 L45 61 L68 38" stroke="#FDE047" strokeWidth="6" strokeLinecap="round" strokeLinejoin="round" />
      </svg>
      {showLabel && (
        <div className="flex flex-col text-left">
          <span className="text-sm font-extrabold tracking-tight text-blue-900 leading-tight">SIKOMANDO</span>
          <span className="text-[10px] font-medium text-slate-500 leading-tight">Sistem Hibah Sulut</span>
        </div>
      )}
    </div>
  );
}

/**
 * The unified Official Brand Header displaying the 3 official logos side by side:
 * 1. Logo Pemprov Sulawesi Utara
 * 2. Logo Biro Kesejahteraan Rakyat (Biro Kesra)
 * 3. Logo SIKOMANDO
 */
export function TripleBrandHeader({ size = "md", theme = "light" }) {
  const sizeClasses = {
    sm: "w-8 h-8",
    md: "w-9 h-9",
    lg: "w-11 h-11",
  };

  const isDark = theme === "dark";

  return (
    <div className="flex items-center gap-3 select-none">
      {/* 3 Logos Container with subtle vertical separators */}
      <div className="flex items-center gap-2 bg-white/90 dark:bg-slate-900/90 p-1.5 rounded-xl border border-slate-200/80 shadow-xs">
        <LogoPemprovSulut className={sizeClasses[size]} />
        <div className="w-[1px] h-6 bg-slate-200" />
        <LogoBiroKesra className={sizeClasses[size]} />
        <div className="w-[1px] h-6 bg-slate-200" />
        <LogoSikomando className={sizeClasses[size]} />
      </div>

      {/* Brand Text Block */}
      <div className="flex flex-col">
        <div className="flex items-center gap-1.5">
          <span className={`font-black tracking-tight ${size === 'lg' ? 'text-xl' : 'text-base'} ${isDark ? 'text-white' : 'text-slate-900'}`}>
            SIKOMANDO
          </span>
          <span className="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-800 border border-blue-200">
            SULUT
          </span>
        </div>
        <span className={`text-[11px] font-medium leading-tight ${isDark ? 'text-slate-300' : 'text-slate-500'}`}>
          Biro Kesra Setda Prov. Sulawesi Utara
        </span>
      </div>
    </div>
  );
}

export default TripleBrandHeader;

