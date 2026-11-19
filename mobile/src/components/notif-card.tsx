import { useEffect, useState } from "react";
import { Linking, Text, View } from "react-native";

import { getPushState, PushState, registerForPush } from "@/src/push";
import { font, makeStyles, radius, spacing, useTheme } from "@/src/theme";
import { Icon } from "./icon";
import { Button } from "./ui";

// Penjelasan sebelum izin notifikasi; tombol Buka Pengaturan bila izin diblokir.
export function NotifCard() {
  const [state, setState] = useState<PushState | null>(null);
  const [busy, setBusy] = useState(false);
  const s = useStyles();
  const { colors } = useTheme();

  useEffect(() => {
    getPushState().then(setState);
  }, []);

  if (!state || state === "granted" || state === "unsupported") return null;

  const blocked = state === "blocked";
  return (
    <View style={s.card} testID="notif-permission-card">
      <View style={s.icon}>
        <Icon name="notifications" size={22} color={colors.onBrandSecondary} />
      </View>
      <View style={{ flex: 1 }}>
        <Text style={s.title}>Aktifkan notifikasi</Text>
        <Text style={s.desc}>
          {blocked
            ? "Notifikasi diblokir. Buka Pengaturan untuk menerima kabar surat & disposisi baru."
            : "Dapatkan pemberitahuan langsung saat ada surat masuk, disposisi, atau surat yang perlu Anda verifikasi."}
        </Text>
        <Button
          label={blocked ? "Buka Pengaturan" : "Aktifkan"}
          icon={blocked ? "settings-outline" : "notifications-outline"}
          loading={busy}
          style={{ marginTop: spacing.md, alignSelf: "flex-start", minHeight: 44 }}
          testID="notif-enable-button"
          onPress={async () => {
            if (blocked) return Linking.openSettings();
            setBusy(true);
            setState(await registerForPush(true));
            setBusy(false);
          }}
        />
      </View>
    </View>
  );
}

const useStyles = makeStyles((c) => ({
  card: { flexDirection: "row", gap: spacing.md, backgroundColor: c.warningSoft, borderRadius: radius.md, padding: spacing.lg, marginTop: spacing.xl },
  icon: { width: 40, height: 40, borderRadius: 20, backgroundColor: c.brandSecondary, alignItems: "center", justifyContent: "center" },
  title: { fontFamily: font.bold, fontSize: 15, color: c.onBrandSecondary },
  desc: { fontFamily: font.regular, fontSize: 13, color: c.onSurfaceTertiary, marginTop: 2, lineHeight: 19 },
}));
