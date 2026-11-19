import { createContext, ReactNode, useCallback, useContext, useRef, useState } from "react";
import { Text, View } from "react-native";
import Animated, { FadeInUp, FadeOutUp } from "react-native-reanimated";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { font, makeStyles, radius, spacing, useTheme } from "@/src/theme";
import { Icon } from "./icon";

type Tone = "success" | "error" | "info";
const Ctx = createContext<(msg: string, tone?: Tone) => void>(() => {});

export function ToastProvider({ children }: { children: ReactNode }) {
  const [toast, setToast] = useState<{ msg: string; tone: Tone; id: number } | null>(null);
  const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const insets = useSafeAreaInsets();
  const styles = useStyles();
  const { colors } = useTheme();

  const show = useCallback((msg: string, tone: Tone = "success") => {
    if (timer.current) clearTimeout(timer.current);
    setToast({ msg, tone, id: Date.now() });
    timer.current = setTimeout(() => setToast(null), 3200);
  }, []);

  const bg = toast?.tone === "error" ? colors.error : toast?.tone === "info" ? colors.surfaceInverse : colors.success;

  return (
    <Ctx.Provider value={show}>
      {children}
      {toast && (
        <Animated.View
          key={toast.id}
          entering={FadeInUp}
          exiting={FadeOutUp}
          pointerEvents="none"
          style={[styles.wrap, { top: insets.top + spacing.sm }]}
        >
          <View style={[styles.toast, { backgroundColor: bg }]} testID="app-toast">
            <Icon name={toast.tone === "error" ? "alert-circle" : "checkmark-circle"} size={20} color={colors.onSuccess} />
            <Text style={styles.text} testID="app-toast-message">{toast.msg}</Text>
          </View>
        </Animated.View>
      )}
    </Ctx.Provider>
  );
}

export const useToast = () => useContext(Ctx);

const useStyles = makeStyles((c) => ({
  wrap: { position: "absolute", left: spacing.lg, right: spacing.lg, alignItems: "center" },
  toast: {
    flexDirection: "row",
    alignItems: "center",
    gap: spacing.sm,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    borderRadius: radius.md,
    maxWidth: 480,
    shadowColor: c.surfaceInverse,
    shadowOpacity: 0.2,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: 4 },
    elevation: 6,
  },
  text: { color: c.onSuccess, fontFamily: font.semibold, fontSize: 14, flexShrink: 1 },
}));
