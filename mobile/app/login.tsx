import { Image } from "expo-image";
import { LinearGradient } from "expo-linear-gradient";
import * as WebBrowser from "expo-web-browser";
import { useEffect, useState } from "react";
import { Pressable, Text, TextInput, View } from "react-native";
import { KeyboardAwareScrollView } from "react-native-keyboard-controller";
import Animated, { Easing, FadeIn, FadeInDown, useAnimatedStyle, useSharedValue, withRepeat, withSequence, withTiming } from "react-native-reanimated";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { useAuth } from "@/src/auth";
import { Gate3D } from "@/src/components/gate3d";
import { Icon } from "@/src/components/icon";
import { Button, Segmented } from "@/src/components/ui";
import { ESURAT_URL } from "@/src/config";
import { font, makeStyles, radius, shadow, spacing, useTheme } from "@/src/theme";

const POSES = {
  typing: require("../assets/images/login/man-typing.webp"),
  cover: require("../assets/images/login/man-cover.webp"),
  thumb: require("../assets/images/login/man-thumb.webp"),
};
type Pose = keyof typeof POSES;

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
  const [pose, setPose] = useState<Pose>("typing");
  const [bubble, setBubble] = useState("Om Swastyastu! Silakan masuk.");

  // Amplop melayang & karakter "mengetik" seperti di web.
  const float = useSharedValue(0);
  const bounce = useSharedValue(0);
  useEffect(() => {
    float.value = withRepeat(withTiming(1, { duration: 2600, easing: Easing.inOut(Easing.sin) }), -1, true);
  }, [float]);
  const envStyle = useAnimatedStyle(() => ({ transform: [{ translateY: -14 * float.value }, { rotate: `${6 * float.value}deg` }] }));
  const charStyle = useAnimatedStyle(() => ({ transform: [{ translateY: bounce.value }] }));
  const hop = () => {
    bounce.value = withSequence(withTiming(-4, { duration: 70 }), withTiming(0, { duration: 70 }));
  };

  const submit = async () => {
    if (!username.trim() || !password) {
      setError(`${tipe === "pegawai" ? "NIP" : "Username"} dan password wajib diisi.`);
      setBubble("Isi dulu datanya, ya.");
      return;
    }
    setLoading(true);
    setError(null);
    setBubble("Sebentar, sedang memverifikasi…");
    try {
      await signIn(username, password, tipe);
      setPose("thumb");
      setBubble("Mantap! Selamat bekerja.");
    } catch (e: any) {
      setError(e?.message ?? "Gagal masuk.");
      setPose("typing");
      setBubble("Waduh, coba periksa lagi ya.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={s.root} testID="login-screen">
      <LinearGradient colors={["#FDE4E8", "#FFF4F5", "#F9DBE1"]} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={s.abs} />
      <View style={[s.orb, { top: 60, left: -60, backgroundColor: "rgba(230,57,70,0.18)" }]} />
      <View style={[s.orb, { top: 240, right: -80, backgroundColor: "rgba(244,162,97,0.22)" }]} />
      <KeyboardAwareScrollView bottomOffset={24} contentContainerStyle={{ flexGrow: 1 }} keyboardShouldPersistTaps="handled">
        <View style={[s.top, { paddingTop: insets.top + spacing.md }]}>
          <Animated.View entering={FadeInDown.duration(500)} style={s.brandPill}>
            <Image source={require("../assets/images/logo.png")} style={{ width: 28, height: 28 }} contentFit="contain" />
            <View>
              <Text style={s.brandTitle}>E-Surat Tabanan</Text>
              <Text style={s.brandSub}>Kabupaten Tabanan</Text>
            </View>
          </Animated.View>
          <Pressable onPress={() => WebBrowser.openBrowserAsync(`${ESURAT_URL}/login`)} style={s.webBtn} testID="login-open-web-link">
            <Icon name="globe-outline" size={18} color={colors.onSurface} />
          </Pressable>
        </View>

        <View style={s.stage} testID="login-hero">
          <Gate3D style={s.gate} />
          <Animated.View style={[s.envelope, envStyle]} pointerEvents="none">
            <Image source={require("../assets/images/login/envelope.webp")} style={{ width: "100%", height: "100%" }} contentFit="contain" />
          </Animated.View>
          <Image source={require("../assets/images/login/plant.webp")} style={s.plant} contentFit="contain" pointerEvents="none" />
          <Animated.View style={[s.character, charStyle]} pointerEvents="none" testID="login-character">
            {(Object.keys(POSES) as Pose[]).map((k) => (
              <Image key={k} source={POSES[k]} style={[s.pose, { opacity: pose === k ? 1 : 0 }]} contentFit="contain" transition={250} />
            ))}
          </Animated.View>
          <Animated.View key={bubble} entering={FadeIn.duration(250)} style={s.bubble} testID="login-speech-bubble">
            <Text style={s.bubbleText}>{bubble}</Text>
          </Animated.View>
        </View>

        <Animated.View entering={FadeInDown.delay(150).duration(500)} style={[s.sheet, { paddingBottom: insets.bottom + spacing.xl }]}>
          <Text style={s.eyebrow}>Sistem Persuratan Elektronik</Text>
          <Text style={s.title}>Masuk ke{"\n"}akun Anda</Text>
          <View style={{ marginTop: spacing.lg }}>
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

          <View style={[s.inputWrap, { marginTop: spacing.lg }]}>
            <View style={s.inputIcon}>
              <Icon name="person-outline" size={18} color={colors.brandPrimary} />
            </View>
            <TextInput
              value={username}
              onChangeText={(v) => {
                setUsername(v);
                setPose("typing");
                hop();
                setBubble(v.trim() ? `Halo, ${v.trim().length > 14 ? v.trim().slice(0, 14) + "…" : v.trim()}!` : "Ketik username kamu ya…");
              }}
              onFocus={() => {
                setPose("typing");
                setBubble(tipe === "pegawai" ? "Ketik NIP kamu ya…" : "Ketik username kamu ya…");
              }}
              placeholder={tipe === "pegawai" ? "NIP" : "Username"}
              placeholderTextColor={colors.muted}
              autoCapitalize="none"
              autoCorrect={false}
              style={s.input}
              testID="login-username-input"
              returnKeyType="next"
            />
          </View>

          <View style={[s.inputWrap, { marginTop: spacing.md }]}>
            <View style={s.inputIcon}>
              <Icon name="lock-closed-outline" size={18} color={colors.brandPrimary} />
            </View>
            <TextInput
              value={password}
              onChangeText={(v) => {
                setPassword(v);
                hop();
              }}
              onFocus={() => {
                setPose("cover");
                setBubble("Tenang, saya tutup mata. Tidak mengintip!");
              }}
              onBlur={() => {
                setPose("typing");
                setBubble("Siap, silakan tekan Masuk.");
              }}
              placeholder="Password"
              placeholderTextColor={colors.muted}
              secureTextEntry={!show}
              autoCapitalize="none"
              style={s.input}
              testID="login-password-input"
              returnKeyType="go"
              onSubmitEditing={submit}
            />
            <Pressable
              onPress={() => {
                setShow((v) => !v);
                if (!show) {
                  setPose("cover");
                  setBubble("Password terlihat… saya tetap tutup mata!");
                }
              }}
              hitSlop={10}
              style={s.eye}
              testID="login-toggle-password"
            >
              <Icon name={show ? "eye-off-outline" : "eye-outline"} size={20} color={colors.muted} />
            </Pressable>
          </View>

          {error ? (
            <View style={s.error} testID="login-error-message">
              <Icon name="alert-circle" size={18} color={colors.error} />
              <Text style={s.errorText}>{error}</Text>
            </View>
          ) : null}

          <Button label="Masuk" icon="arrow-forward" onPress={submit} loading={loading} style={{ marginTop: spacing.xl, minHeight: 58 }} testID="login-submit-button" />
          <Text style={s.footer}>© {new Date().getFullYear()} Pemerintah Kabupaten Tabanan</Text>
        </Animated.View>
      </KeyboardAwareScrollView>
    </View>
  );
}

const useStyles = makeStyles((c) => ({
  root: { flex: 1, backgroundColor: c.surfaceSecondary, overflow: "hidden" },
  abs: { position: "absolute", left: 0, right: 0, top: 0, bottom: 0 },
  orb: { position: "absolute", width: 240, height: 240, borderRadius: 120 },
  top: { flexDirection: "row", alignItems: "center", justifyContent: "space-between", paddingHorizontal: spacing.lg },
  brandPill: { flexDirection: "row", alignItems: "center", gap: spacing.sm, backgroundColor: c.glass, borderRadius: radius.pill, paddingLeft: spacing.sm, paddingRight: spacing.lg, height: 48, ...shadow },
  brandTitle: { fontFamily: font.heavy, fontSize: 14, color: c.onSurface },
  brandSub: { fontFamily: font.regular, fontSize: 10, color: c.muted },
  webBtn: { width: 48, height: 48, borderRadius: 24, backgroundColor: c.glass, alignItems: "center", justifyContent: "center", ...shadow },
  stage: { height: 330, marginTop: spacing.sm, width: "100%", maxWidth: 520, alignSelf: "center" },
  gate: { position: "absolute", left: -20, right: -20, top: 0, bottom: 30 },
  envelope: { position: "absolute", left: "6%", top: "6%", width: "18%", aspectRatio: 1 },
  plant: { position: "absolute", right: "2%", bottom: 0, width: "18%", height: "34%" },
  character: { position: "absolute", left: "22%", right: "22%", bottom: -6, height: "62%" },
  pose: { position: "absolute", left: 0, right: 0, top: 0, bottom: 0 },
  bubble: { position: "absolute", right: spacing.lg, top: spacing.md, maxWidth: "46%", backgroundColor: c.surface, borderRadius: 22, borderBottomRightRadius: 6, paddingHorizontal: spacing.md, paddingVertical: spacing.sm, ...shadow },
  bubbleText: { fontFamily: font.bold, fontSize: 12, lineHeight: 16, color: c.onSurface },
  sheet: { flex: 1, backgroundColor: c.surface, borderTopLeftRadius: 36, borderTopRightRadius: 36, paddingHorizontal: spacing.xl, paddingTop: spacing.xl, width: "100%", maxWidth: 560, alignSelf: "center", ...shadow },
  eyebrow: { fontFamily: font.semibold, fontSize: 12, color: c.brandPrimary, letterSpacing: 0.6, textTransform: "uppercase" },
  title: { fontFamily: font.heavy, fontSize: 30, lineHeight: 36, color: c.onSurface, marginTop: spacing.xs },
  inputWrap: { flexDirection: "row", alignItems: "center", gap: spacing.md, backgroundColor: c.surfaceSecondary, borderRadius: radius.pill, paddingLeft: 6, paddingRight: spacing.sm, height: 60 },
  inputIcon: { width: 48, height: 48, borderRadius: 24, backgroundColor: c.brandTertiary, alignItems: "center", justifyContent: "center" },
  input: { flex: 1, fontFamily: font.semibold, fontSize: 15, color: c.onSurface, height: "100%", outlineStyle: "none" } as any,
  eye: { width: 44, height: 44, alignItems: "center", justifyContent: "center" },
  error: { flexDirection: "row", gap: spacing.sm, alignItems: "center", backgroundColor: c.errorSoft, borderRadius: radius.md, padding: spacing.md, marginTop: spacing.lg },
  errorText: { flex: 1, fontFamily: font.semibold, fontSize: 13, color: c.error },
  footer: { fontFamily: font.regular, fontSize: 11, color: c.muted, textAlign: "center", marginTop: spacing.lg },
}));
