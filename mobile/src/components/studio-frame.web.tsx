import { createElement, forwardRef, useEffect, useImperativeHandle, useRef } from "react";
import { StyleProp, View, ViewStyle } from "react-native";

export type FrameHandle = { post: (cmd: any) => void };
type Props = { html: string; onMessage: (raw: string) => void; style?: StyleProp<ViewStyle>; testID?: string };

// Web preview: iframe srcDoc dengan protokol postMessage yang sama.
export const StudioFrame = forwardRef<FrameHandle, Props>(function StudioFrame({ html, onMessage, style, testID }, ref) {
  const frame = useRef<HTMLIFrameElement | null>(null);
  const cb = useRef(onMessage);
  cb.current = onMessage;

  useImperativeHandle(ref, () => ({
    post: (cmd) => frame.current?.contentWindow?.postMessage({ __pdfcmd: true, cmd }, "*"),
  }));

  useEffect(() => {
    const h = (e: MessageEvent) => {
      if (e.source === frame.current?.contentWindow && e.data?.__pdfstudio) cb.current(e.data.payload);
    };
    window.addEventListener("message", h);
    return () => window.removeEventListener("message", h);
  }, []);

  return (
    <View style={[{ overflow: "hidden" }, style]} testID={testID}>
      {createElement("iframe", {
        ref: frame,
        srcDoc: html,
        title: "Pratinjau surat",
        style: { border: 0, width: "100%", height: "100%", display: "block" },
      })}
    </View>
  );
});
