// Design tokens E-Surat Tabanan (dari /app/design_guidelines.json). Light theme.
// Catatan: di kode ini `surface` = kartu/permukaan putih, `surfaceSecondary` = latar layar (off-white hangat).
import { useMemo } from "react";
import { Appearance, StyleSheet, useColorScheme } from "react-native";

export type ColorScheme = "light" | "dark";

const light = {
  surface: "#FFFFFF",
  onSurface: "#1A1918",
  surfaceSecondary: "#F7F4F2",
  onSurfaceSecondary: "#1A1918",
  surfaceTertiary: "#F2EFEA",
  onSurfaceTertiary: "#4A4643",
  surfaceInverse: "#1A1918",
  onSurfaceInverse: "#FFFFFF",
  muted: "#7D7975",

  brand: "#E63946",
  onBrand: "#FFFFFF",
  brandPrimary: "#E63946",
  onBrandPrimary: "#FFFFFF",
  brandSecondary: "#F4A261",
  onBrandSecondary: "#6B3410",
  brandTertiary: "#FDECEE",
  onBrandTertiary: "#C1121F",
  brandDeep: "#7A1020",

  success: "#2A9D8F",
  onSuccess: "#FFFFFF",
  successSoft: "#E3F4F1",
  warning: "#F4A261",
  onWarning: "#1A1918",
  warningSoft: "#FDF1E6",
  error: "#E63946",
  onError: "#FFFFFF",
  errorSoft: "#FDECEE",
  info: "#457B9D",
  onInfo: "#FFFFFF",
  infoSoft: "#E6EFF5",

  border: "#E8E4DF",
  borderStrong: "#D6CFC7",
  divider: "#EFEBE7",
  scrim: "rgba(26,25,24,0.45)",
  glass: "rgba(255,255,255,0.86)",
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
export const radius = { sm: 8, md: 16, lg: 28, pill: 999 };
export const font = {
  regular: "Jakarta-400",
  semibold: "Jakarta-600",
  bold: "Jakarta-700",
  heavy: "Jakarta-800",
};

export const shadow = {
  shadowColor: "#1A1918",
  shadowOpacity: 0.06,
  shadowRadius: 16,
  shadowOffset: { width: 0, height: 6 },
  elevation: 2,
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
