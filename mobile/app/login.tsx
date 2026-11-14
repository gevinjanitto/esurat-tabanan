import { Image } from "expo-image";
import { LinearGradient } from "expo-linear-gradient";
import * as WebBrowser from "expo-web-browser";
import { useState } from "react";
import { Pressable, Text, TextInput, View } from "react-native";
import { KeyboardAwareScrollView } from "react-native-keyboard-controller";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { useAuth } from "@/src/auth";
import { Icon } from "@/src/components/icon";
import { Button, Segmented } from "@/src/components/ui";
import { ESURAT_URL } from "@/src/config";
import { font, makeStyles, radius, spacing, useTheme } from "@/src/theme";

const BG = "https://images.unsplash.com/photo-1714779573266-e81df2c73910?crop=entropy&cs=srgb&fm=jpg&q=85&w=1200";

export default function Login() {
  const { signIn } = useAuth();
  const insets = useSafeAreaInsets();
  const s = useStyles();
  const { colors } = useTheme();
  const [tipe, setTipe] = useState<"pegawai" | "admin">("pegawai");
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [show, setShow] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const submit = async () => {
    if (!username.trim() || !password) {
      setError(`${tipe === "pegawai" ? "NIP" : "Username"} dan password wajib diisi.`);
      return;
    }
    setLoading(true);
    setError(null);
    try {
      await signIn(username, password, tipe);
    } catch (e: any) {
      setError(e?.message ?? "Gagal masuk.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={s.root} testID="login-screen">
      <KeyboardAwareScrollView bottomOffset={24} contentContainerStyle={{ flexGrow: 1 }} keyboardShouldPersistTaps="handled">
        <View style={[s.hero, { paddingTop: insets.top + spacing.xl }]}>
          <Image source={{ uri: BG }} style={StyleSheet_absolute} contentFit="cover" transition={300} />
          <LinearGradient colors={["rgba(76,5,25,0.15)", "rgba(76,5,25,0.55)", colors.surface]} locations={[0, 0.6, 1]} style={StyleSheet_absolute} />
          <View style={s.logoWrap}>
            <Image source={require("../assets/images/logo.png")} style={s.logo} contentFit="contain" />
          </View>
          <Text style={s.greet}>Om Swastyastu</Text>
          <Text style={s.brand}>E-Surat Tabanan</Text>
          <Text style={s.tag}>Sistem Persuratan Elektronik Kabupaten Tabanan</Text>
        </View>

        <View style={[s.sheet, { paddingBottom: insets.bottom + spacing.xl }]}>
          <Text style={s.title}>Masuk ke akun Anda</Text>
          <Text style={s.sub}>Sekali masuk, aplikasi tetap terhubung sampai Anda keluar.</Text>
          <View style={{ marginTop: spacing.xl }}>
            <Segmented
              testID="login-type"
              value={tipe}
              onChange={(k) => {
                setTipe(k);
                setError(null);
              }}
              options={[
                { key: "pegawai", label: "Pegawai (NIP)" },
                { key: "admin", label: "Admin / Operator" },
              ]}
            />
          </View>

          <Text style={s.label}>{tipe === "pegawai" ? "NIP" : "Username"}</Text>
          <View style={s.inputWrap}>
            <Icon name="person-outline" size={18} color={colors.muted} />
            <TextInput
              value={username}
              onChangeText={setUsername}
              placeholder={tipe === "pegawai" ? "Masukkan NIP" : "Masukkan username"}
              placeholderTextColor={colors.muted}
              autoCapitalize="none"
              autoCorrect={false}
              keyboardType={tipe === "pegawai" ? "default" : "default"}
              style={s.input}
              testID="login-username-input"
              returnKeyType="next"
            />
          </View>

          <Text style={s.label}>Password</Text>
          <View style={s.inputWrap}>
            <Icon name="lock-closed-outline" size={18} color={colors.muted} />
            <TextInput
              value={password}
              onChangeText={setPassword}
              placeholder="••••••••"
              placeholderTextColor={colors.muted}
              secureTextEntry={!show}
              autoCapitalize="none"
              style={s.input}
              testID="login-password-input"
              returnKeyType="go"
              onSubmitEditing={submit}
            />
            <Pressable onPress={() => setShow((v) => !v)} hitSlop={10} style={{ width: 44, height: 44, alignItems: "center", justifyContent: "center", marginRight: -spacing.md }} testID="login-toggle-password">
              <Icon name={show ? "eye-off-outline" : "eye-outline"} size={20} color={colors.muted} />
            </Pressable>
          </View>

          {error ? (
            <View style={s.error} testID="login-error-message">
              <Icon name="alert-circle" size={18} color={colors.error} />
              <Text style={s.errorText}>{error}</Text>
            </View>
          ) : null}

          <Button label="Masuk" icon="arrow-forward" onPress={submit} loading={loading} style={{ marginTop: spacing.xl }} testID="login-submit-button" />

          <Pressable onPress={() => WebBrowser.openBrowserAsync(`${ESURAT_URL}/login`)} style={s.webLink} testID="login-open-web-link">
            <Icon name="globe-outline" size={16} color={colors.brandPrimary} />
            <Text style={s.webLinkText}>Buka versi web</Text>
          </Pressable>
          <Text style={s.footer}>© {new Date().getFullYear()} Kabupaten Tabanan · E-Surat</Text>
        </View>
      </KeyboardAwareScrollView>
    </View>
  );
}

const StyleSheet_absolute = { position: "absolute" as const, left: 0, right: 0, top: 0, bottom: 0 };

const useStyles = makeStyles((c) => ({
  root: { flex: 1, backgroundColor: c.surface },
  hero: { minHeight: 300, paddingHorizontal: spacing.xl, paddingBottom: spacing["2xl"], justifyContent: "flex-end", overflow: "hidden" },
  logoWrap: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: c.surface,
    alignItems: "center",
    justifyContent: "center",
    shadowColor: c.brandDeep,
    shadowOpacity: 0.25,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: 6 },
    elevation: 6,
  },
  logo: { width: 56, height: 56 },
  greet: { fontFamily: font.semibold, color: c.onBrand, fontSize: 14, marginTop: spacing.lg, letterSpacing: 0.5 },
  brand: { fontFamily: font.heavy, color: c.onBrand, fontSize: 32, marginTop: 2 },
  tag: { fontFamily: font.regular, color: c.onBrand, fontSize: 13, opacity: 0.9, marginTop: 2 },
  sheet: { flex: 1, backgroundColor: c.surface, paddingHorizontal: spacing.xl, paddingTop: spacing.sm, width: "100%", maxWidth: 520, alignSelf: "center" },
  title: { fontFamily: font.bold, fontSize: 20, color: c.onSurface },
  sub: { fontFamily: font.regular, fontSize: 13, color: c.muted, marginTop: spacing.xs },
  label: { fontFamily: font.semibold, fontSize: 13, color: c.onSurfaceTertiary, marginTop: spacing.lg, marginBottom: spacing.sm },
  inputWrap: {
    flexDirection: "row",
    alignItems: "center",
    gap: spacing.md,
    backgroundColor: c.surfaceSecondary,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: c.border,
    paddingHorizontal: spacing.lg,
    height: 52,
  },
  input: { flex: 1, fontFamily: font.regular, fontSize: 15, color: c.onSurface, height: "100%" },
  error: { flexDirection: "row", gap: spacing.sm, alignItems: "center", backgroundColor: c.errorSoft, borderRadius: radius.md, padding: spacing.md, marginTop: spacing.lg },
  errorText: { flex: 1, fontFamily: font.semibold, fontSize: 13, color: c.error },
  webLink: { flexDirection: "row", gap: 6, alignItems: "center", justifyContent: "center", marginTop: spacing.lg, minHeight: 44 },
  webLinkText: { fontFamily: font.semibold, fontSize: 14, color: c.brandPrimary },
  footer: { fontFamily: font.regular, fontSize: 11, color: c.muted, textAlign: "center", marginTop: spacing.md },
}));
