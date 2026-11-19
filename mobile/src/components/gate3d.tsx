import { Image } from "expo-image";
import { useState } from "react";
import { StyleProp, StyleSheet, View, ViewStyle } from "react-native";

import { BACKEND_URL, ESURAT_URL } from "@/src/config";
import { GATE_HTML } from "@/src/gate3d-html";
import { StudioFrame } from "./studio-frame";

const HTML = GATE_HTML.replace("__ORNAMENT__", BACKEND_URL ? `${BACKEND_URL}/api/static/gate-ornament.jpg` : `${ESURAT_URL}/images/gate-ornament.jpg`);

// Model 3D Pura Tanah Lot yang sama dengan halaman login web. Gambar statis tampil sampai WebGL siap.
export function Gate3D({ style }: { style?: StyleProp<ViewStyle> }) {
  const [ready, setReady] = useState(false);
  return (
    <View style={style} testID="login-gate-3d">
      <StudioFrame
        html={HTML}
        style={StyleSheet.absoluteFill}
        onMessage={(raw) => {
          try {
            if (JSON.parse(raw).type === "ready") setTimeout(() => setReady(true), 700);
          } catch {}
        }}
      />
      {!ready ? (
        <Image source={require("../../assets/images/login/gate.webp")} style={[StyleSheet.absoluteFill, { margin: "8%" }]} contentFit="contain" pointerEvents="none" />
      ) : null}
    </View>
  );
}
