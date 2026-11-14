import { useEffect, useState } from "react";
import { Modal, Platform, Pressable, Text, TextInput, View } from "react-native";
import { KeyboardAvoidingView } from "react-native-keyboard-controller";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { font, makeStyles, radius, spacing, useTheme } from "@/src/theme";
import { Button } from "./ui";

export type SheetConfig = {
  title: string;
  description?: string;
  inputLabel?: string;
  placeholder?: string;
  secure?: boolean;
  required?: boolean;
  confirmLabel: string;
  danger?: boolean;
  onConfirm: (text: string) => Promise<void>;
};

export function ActionSheet({ config, onClose }: { config: SheetConfig | null; onClose: () => void }) {
  const [text, setText] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const insets = useSafeAreaInsets();
  const styles = useStyles();
  const { colors } = useTheme();

  useEffect(() => {
    setText("");
    setError(null);
    setLoading(false);
  }, [config]);

  const submit = async () => {
    if (!config) return;
    if (config.required && !text.trim()) {
      setError(`${config.inputLabel ?? "Isian"} wajib diisi.`);
      return;
    }
    setLoading(true);
    setError(null);
    try {
      await config.onConfirm(text);
      onClose();
    } catch (e: any) {
      setError(e?.message ?? "Terjadi kesalahan.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <Modal visible={!!config} transparent animationType="slide" onRequestClose={onClose} statusBarTranslucent>
      <KeyboardAvoidingView behavior={Platform.OS === "ios" ? "padding" : "height"} style={{ flex: 1 }}>
        <Pressable style={styles.backdrop} onPress={onClose} testID="sheet-backdrop" />
        <View style={[styles.sheet, { paddingBottom: insets.bottom + spacing.lg }]} testID="action-sheet">
          <View style={styles.handle} />
          <Text style={styles.title}>{config?.title}</Text>
          {config?.description ? <Text style={styles.desc}>{config.description}</Text> : null}
          {config?.inputLabel ? (
            <>
              <Text style={styles.label}>{config.inputLabel}</Text>
              <TextInput
                value={text}
                onChangeText={setText}
                placeholder={config.placeholder}
                placeholderTextColor={colors.muted}
                secureTextEntry={config.secure}
                multiline={!config.secure}
                autoFocus
                style={[styles.input, !config.secure && { minHeight: 96, textAlignVertical: "top" }]}
                testID="action-sheet-input"
              />
            </>
          ) : null}
          {error ? <Text style={styles.error} testID="action-sheet-error">{error}</Text> : null}
          <View style={styles.row}>
            <Button label="Batal" variant="secondary" onPress={onClose} style={{ flex: 1 }} testID="action-sheet-cancel" />
            <Button
              label={config?.confirmLabel ?? "OK"}
              variant={config?.danger ? "danger" : "primary"}
              onPress={submit}
              loading={loading}
              style={{ flex: 1 }}
              testID="action-sheet-confirm"
            />
          </View>
        </View>
      </KeyboardAvoidingView>
    </Modal>
  );
}

const useStyles = makeStyles((c) => ({
  backdrop: { flex: 1, backgroundColor: c.scrim },
  sheet: {
    backgroundColor: c.surface,
    borderTopLeftRadius: radius.lg,
    borderTopRightRadius: radius.lg,
    paddingHorizontal: spacing.xl,
    paddingTop: spacing.md,
  },
  handle: { width: 40, height: 5, borderRadius: 3, backgroundColor: c.surfaceTertiary, alignSelf: "center", marginBottom: spacing.lg },
  title: { fontFamily: font.bold, fontSize: 20, color: c.onSurface },
  desc: { fontFamily: font.regular, fontSize: 14, color: c.muted, marginTop: spacing.xs },
  label: { fontFamily: font.semibold, fontSize: 13, color: c.onSurfaceTertiary, marginTop: spacing.lg, marginBottom: spacing.sm },
  input: {
    backgroundColor: c.surfaceSecondary,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: c.border,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    fontFamily: font.regular,
    fontSize: 15,
    color: c.onSurface,
    minHeight: 50,
  },
  error: { color: c.error, fontFamily: font.semibold, fontSize: 13, marginTop: spacing.sm },
  row: { flexDirection: "row", gap: spacing.md, marginTop: spacing.xl },
}));
