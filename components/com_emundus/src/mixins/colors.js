var colors = {
	methods: {
		lightenColor(color, percent) {
			let r = parseInt(color.slice(1, 3), 16);
			let g = parseInt(color.slice(3, 5), 16);
			let b = parseInt(color.slice(5, 7), 16);

			// Convertir RGB en HSL
			let hsl = this.rgbToHsl(r, g, b);

			// Augmenter la lumière (Lightness)
			if (hsl[2] === 0) {
				hsl[2] = Math.min(1, percent / 100); // Si noir pur, appliquer directement le pourcentage
			} else {
				hsl[2] = Math.min(1, hsl[2] + (1 - hsl[2]) * (percent / 100));
				// Éclaircissement proportionnel : plus la couleur est sombre, plus elle est boostée
			}
			/*ANCIENNE FORMULE : hsl[2] = Math.min(1, hsl[2] + percent / 100);*/

			// Reconvertir en RGB puis en Hex
			let newRgb = this.hslToRgb(hsl[0], hsl[1], hsl[2]);
			return this.rgbToHex(newRgb[0], newRgb[1], newRgb[2]);
		},

		rgbToHsl(r, g, b) {
			((r /= 255), (g /= 255), (b /= 255));
			let max = Math.max(r, g, b),
				min = Math.min(r, g, b);
			let h,
				s,
				l = (max + min) / 2;

			if (max === min) {
				h = s = 0;
			} else {
				let d = max - min;
				s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
				switch (max) {
					case r:
						h = (g - b) / d + (g < b ? 6 : 0);
						break;
					case g:
						h = (b - r) / d + 2;
						break;
					case b:
						h = (r - g) / d + 4;
						break;
				}
				h /= 6;
			}
			return [h, s, l];
		},

		// Convertir HSL en RGB
		hslToRgb(h, s, l) {
			let r, g, b;

			if (s === 0) {
				r = g = b = l;
			} else {
				function hue2rgb(p, q, t) {
					if (t < 0) t += 1;
					if (t > 1) t -= 1;
					if (t < 1 / 6) return p + (q - p) * 6 * t;
					if (t < 1 / 2) return q;
					if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
					return p;
				}

				let q = l < 0.5 ? l * (1 + s) : l + s - l * s;
				let p = 2 * l - q;
				r = hue2rgb(p, q, h + 1 / 3);
				g = hue2rgb(p, q, h);
				b = hue2rgb(p, q, h - 1 / 3);
			}

			return [Math.round(r * 255), Math.round(g * 255), Math.round(b * 255)];
		},

		// Convertir RGB en Hex
		rgbToHex(r, g, b) {
			return '#' + ((1 << 24) | (r << 16) | (g << 8) | b).toString(16).slice(1);
		},
	},
};

export default colors;
