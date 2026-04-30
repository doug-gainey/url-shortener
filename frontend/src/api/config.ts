import { api } from "./client";

export type AppConfig = {
  base_url: string;
};

export async function fetchAppConfig(): Promise<AppConfig> {
  const response = await api.get("/config");
  return response.data.data;
}
