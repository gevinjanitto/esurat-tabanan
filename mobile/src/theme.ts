// Design tokens E-Surat Tabanan (dari /app/design_guidelines.json). Light theme.
import { useMemo } from "react";
import { Appearance, StyleSheet, useColorScheme } from "react-native";

export type ColorScheme = "light" | "dark";

const light = {
  surface: "#ffffff",
  onSurface: "#1c1917",
  surfaceSecondary: "#f5f5f4",
  onSurfaceSecondary: "#292524",
  surfaceTertiary: "#e7e5e4",
  onSurfaceTertiary: "#44403c",
  surfaceInverse: "#1c1917",
  onSurfaceInverse: "#ffffff",
  muted: "#78716c",

  brand: "#a3122f",
  onBrand: "#ffffff",
  brandPrimary: "#a3122f",
  onBrandPrimary: "#ffffff",
  brandSecondary: "#d4af37",
  onBrandSecondary: "#4c0519",
  brandTertiary: "#fce7f3",
  onBrandTertiary: "#a3122f",
  brandDeep: "#4c0519",

  success: "#166534",
  onSuccess: "#ffffff",
  successSoft: "#dcfce7",
  warning: "#ca8a04",
  onWarning: "#ffffff",
  warningSoft: "#fef3c7",
  error: "#b91c1c",
  onError: "#ffffff",
  errorSoft: "#fee2e2",
  info: "#0369a1",
  onInfo: "#ffffff",
  infoSoft: "#e0f2fe",

  border: "#e7e5e4",
  borderStrong: "#d6d3d1",
  divider: "#f5f5f4",
  scrim: "rgba(28,25,23,0.45)",
};

export type ThemeColors = typeof light;

export const defaultScheme = "light" satisfies ColorScheme;

export const themes: { light: ThemeColors; dark?: ThemeColors } = { light };

export function setColorScheme(scheme: ColorScheme | null) {
  Appearance.setColorScheme?.(scheme ?? "unspecified");
}

setColorScheme?.(themes.dark ? null : defaultScheme);

export const colors = light;

export const spacing = { xs: 4, sm: 8, md: 12, lg: 16, xl: 24, "2xl": 32, "3xl": 48 };
export const radius = { sm: 6, md: 12, lg: 20, pill: 999 };
export const font = {
  regular: "Jakarta-400",
  semibold: "Jakarta-600",
  bold: "Jakarta-700",
  heavy: "Jakarta-800",
};

export function useTheme(): { scheme: ColorScheme; colors: ThemeColors } {
  const system = useColorScheme();
  const scheme: ColorScheme = (system === "light" || system === "dark") && themes[system] ? system : defaultScheme;
  return { scheme, colors: themes[scheme] ?? themes.light };
}

export function makeStyles<T extends StyleSheet.NamedStyles<T> | StyleSheet.NamedStyles<any>>(
  factory: (colors: ThemeColors) => T & StyleSheet.NamedStyles<any>,
): () => T {
  return function useStyles(): T {
    const { colors } = useTheme();
    return useMemo(() => StyleSheet.create(factory(colors)), [colors]);
  };
}
