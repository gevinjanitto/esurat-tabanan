import dayjs from "dayjs";
import "dayjs/locale/id";

dayjs.locale("id");

export const fmtDate = (v?: string | null) => (v ? dayjs(v).format("D MMM YYYY") : "-");
export const fmtDateTime = (v?: string | null) => (v ? dayjs(v).format("D MMM YYYY, HH:mm") : "-");
export const fmtTime = (v?: string | null) => (v ? dayjs(v).format("HH:mm") : "");
export const dayNum = (v?: string | null) => (v ? dayjs(v).format("D") : "-");
export const monthShort = (v?: string | null) => (v ? dayjs(v).format("MMM") : "");

export const initials = (name?: string | null) =>
  (name ?? "?")
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase())
    .join("");
