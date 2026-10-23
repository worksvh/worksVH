/* Výkres dílu na úvodní stránce. */
export function PartSvg() {
  return (
<svg viewBox="0 0 1000 660" className="part-svg" aria-hidden="true" focusable="false">
<defs>
<pattern id="mm" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M20 0H0V20" fill="none" stroke="#DDE1E5" strokeWidth="1"/></pattern>
<pattern id="cm" width="100" height="100" patternUnits="userSpaceOnUse"><rect width="100" height="100" fill="url(#mm)"/><path d="M100 0H0V100" fill="none" stroke="#C6CBD1" strokeWidth="1.2"/></pattern>
</defs>
<rect width="1000" height="660" fill="url(#cm)"/>
<g stroke="#2B2E31" strokeWidth="1.6" strokeLinejoin="round">
<polygon points="140,300 500,480 500,590 140,410" fill="#C3C6CA"/>
<polygon points="500,480 860,300 860,410 500,590" fill="#A3A7AC"/>
<polygon points="500,120 860,300 500,480 140,300" fill="#E6E7E9"/>
<polygon points="340,300 500,220 500,262 382,321" fill="#8E9297"/>
<polygon points="500,220 660,300 618,321 500,262" fill="#7D8186"/>
<polygon points="382,321 500,262 618,321 500,380" fill="#CDD0D3"/>
<line x1="340" y1="300" x2="500" y2="380" fill="none"/>
<line x1="500" y1="380" x2="660" y2="300" fill="none"/>
<ellipse cx="270" cy="300" rx="24" ry="12" fill="#3A3D41"/>
<ellipse cx="730" cy="300" rx="24" ry="12" fill="#3A3D41"/>
<ellipse cx="500" cy="180" rx="24" ry="12" fill="#3A3D41"/>
<ellipse cx="500" cy="420" rx="24" ry="12" fill="#3A3D41"/>
<ellipse cx="680" cy="445" rx="34" ry="20" fill="#5A5E63" transform="rotate(-27 680 445)"/>
<ellipse cx="680" cy="445" rx="18" ry="10" fill="#2B2E31" transform="rotate(-27 680 445)"/>
</g>
<polygon points="400,300 500,250 600,300 500,350" fill="none" stroke="#1A5FD6" strokeWidth="2" strokeDasharray="7 6"/>
<g stroke="#2B2E31" strokeWidth="1.6">
<rect x="489" y="0" width="22" height="118" fill="#B8BDC2"/>
<rect x="491" y="118" width="18" height="140" fill="#D2D5D8"/>
<line x1="491" y1="140" x2="509" y2="128"/>
<line x1="491" y1="170" x2="509" y2="158"/>
<line x1="491" y1="200" x2="509" y2="188"/>
<line x1="491" y1="230" x2="509" y2="218"/>
</g>
<g stroke="#141517" strokeWidth="1.1" fill="#141517">
<line x1="500" y1="596" x2="500" y2="636"/>
<line x1="860" y1="416" x2="860" y2="456"/>
<line x1="500" y1="626" x2="860" y2="446"/>
<path d="M500 626 l12 -2 l-4 7 z"/>
<path d="M860 446 l-12 2 l4 -7 z"/>
<line x1="134" y1="300" x2="96" y2="300"/>
<line x1="134" y1="410" x2="96" y2="410"/>
<line x1="106" y1="300" x2="106" y2="410"/>
<path d="M106 300 l-4 11 h8 z"/>
<path d="M106 410 l-4 -11 h8 z"/>
</g>
<g fontFamily="Exo 2, sans-serif" fontSize="17" fill="#141517">
<text x="712" y="526" transform="rotate(-26.57 712 526)" textAnchor="middle">280</text>
<text x="92" y="360" textAnchor="end">40</text>
<text x="760" y="268">4× Ø12 H7</text>
<text x="530" y="40" fill="#1A5FD6">fréza Ø16</text>
</g>
</svg>
  )
}
