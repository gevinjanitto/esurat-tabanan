import { forwardRef, useImperativeHandle, useRef } from "react";
import { StyleProp, ViewStyle } from "react-native";
import { WebView } from "react-native-webview";

import { BACKEND_URL } from "@/src/config";

export type FrameHandle = { post: (cmd: any) => void };
type Props = { html: string; onMessage: (raw: string) => void; style?: StyleProp<ViewStyle>; testID?: string };

// Native: WebView berbasis origin backend supaya PDF (via proxy) bisa diambil tanpa kendala CORS.
export const StudioFrame = forwardRef<FrameHandle, Props>(function StudioFrame({ html, onMessage, style, testID }, ref) {
  const wv = useRef<WebView>(null);
  useImperativeHandle(ref, () => ({
    post: (cmd) => wv.current?.injectJavaScript(`window.__cmd && window.__cmd(${JSON.stringify(cmd)});true;`),
  }));
  return (
    <WebView
      ref={wv}
      testID={testID}
      source={{ html, baseUrl: BACKEND_URL }}
      originWhitelist={["*"]}
      onMessage={(e) => onMessage(e.nativeEvent.data)}
      javaScriptEnabled
      domStorageEnabled
      nestedScrollEnabled
      setSupportMultipleWindows={false}
      overScrollMode="never"
      style={[{ backgroundColor: "transparent" }, style]}
    />
  );
});
