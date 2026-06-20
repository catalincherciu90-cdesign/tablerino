/** Visual themes, ported from teme.php. */

export interface Theme {
  nume: string;
  primar: string;
  secundar: string;
  accent: string;
  bg: string;
  card_bg: string;
  header_bg: string;
  header_text: string;
  font: string;
  border_r: string;
  pattern: string;
  dark?: boolean;
}

export const THEMES: Record<string, Theme> = {
  italian: {
    nume: '🍕 Italian',
    primar: '#c0392b', secundar: '#27ae60', accent: '#f39c12',
    bg: '#fdf6f0', card_bg: '#fff', header_bg: '#c0392b', header_text: '#fff',
    font: "'Georgia', serif", border_r: '12px',
    pattern: 'repeating-linear-gradient(45deg, rgba(192,57,43,0.04) 0px, rgba(192,57,43,0.04) 1px, transparent 1px, transparent 12px)',
  },
  asian: {
    nume: '🍣 Asian',
    primar: '#c0392b', secundar: '#2c3e50', accent: '#e74c3c',
    bg: '#1a1a1a', card_bg: '#242424', header_bg: '#111', header_text: '#fff',
    font: "'system-ui', sans-serif", border_r: '4px', pattern: 'none', dark: true,
  },
  fastfood: {
    nume: '🍔 Fast Food',
    primar: '#e74c3c', secundar: '#f39c12', accent: '#e74c3c',
    bg: '#fff9f0', card_bg: '#fff', header_bg: '#e74c3c', header_text: '#fff',
    font: "'system-ui', sans-serif", border_r: '8px', pattern: 'none',
  },
  finedining: {
    nume: '🥂 Fine Dining',
    primar: '#c9a84c', secundar: '#1a1a2e', accent: '#c9a84c',
    bg: '#0f0f1a', card_bg: '#1a1a2e', header_bg: '#0f0f1a', header_text: '#c9a84c',
    font: "'Georgia', serif", border_r: '2px', pattern: 'none', dark: true,
  },
  streetfood: {
    nume: '🌮 Street Food',
    primar: '#e67e22', secundar: '#2ecc71', accent: '#e67e22',
    bg: '#f8f9fa', card_bg: '#fff', header_bg: '#2d2d2d', header_text: '#e67e22',
    font: "'system-ui', sans-serif", border_r: '16px', pattern: 'none',
  },
  cafe: {
    nume: '☕ Cafe & Bistro',
    primar: '#6f4e37', secundar: '#a0856c', accent: '#d4a76a',
    bg: '#fdf8f2', card_bg: '#fff', header_bg: '#3d2b1f', header_text: '#f5e6d3',
    font: "'Georgia', serif", border_r: '10px',
    pattern: 'repeating-linear-gradient(0deg, rgba(111,78,55,0.03) 0px, rgba(111,78,55,0.03) 1px, transparent 1px, transparent 20px)',
  },
  grecesc: {
    nume: '🫒 Grecesc',
    primar: '#1a5276', secundar: '#d4ac0d', accent: '#d4ac0d',
    bg: '#f0f4f8', card_bg: '#fff', header_bg: '#1a5276', header_text: '#fff',
    font: "'Georgia', serif", border_r: '50px',
    pattern: 'radial-gradient(circle at 20px 20px, rgba(26,82,118,0.05) 2px, transparent 2px)',
  },
  romanesc: {
    nume: '🥩 Românesc',
    primar: '#922b21', secundar: '#1a5276', accent: '#f39c12',
    bg: '#fdf6ec', card_bg: '#fff', header_bg: '#7b241c', header_text: '#fdebd0',
    font: "'Georgia', serif", border_r: '8px',
    pattern: 'repeating-linear-gradient(90deg, rgba(146,43,33,0.04) 0px, rgba(146,43,33,0.04) 1px, transparent 1px, transparent 30px)',
  },
};

export function getTheme(slug: string): Theme {
  return THEMES[slug] ?? THEMES.italian;
}

/** Generate the CSS custom properties string for a theme (ported from cssVariabile). */
export function cssVariables(theme: Theme): string {
  const dark = theme.dark ? '#e0e0e0' : '#111';
  const darkSub = theme.dark ? '#aaa' : '#666';
  return `
    --primar: ${theme.primar};
    --secundar: ${theme.secundar};
    --accent: ${theme.accent};
    --bg: ${theme.bg};
    --card-bg: ${theme.card_bg};
    --header-bg: ${theme.header_bg};
    --header-text: ${theme.header_text};
    --font: ${theme.font};
    --border-r: ${theme.border_r};
    --pattern: ${theme.pattern};
    --text: ${dark};
    --text-sub: ${darkSub};
  `;
}
