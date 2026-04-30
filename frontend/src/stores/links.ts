import { defineStore } from "pinia";
import { api } from "../api/client";

type LinkItem = {
  id: number;
  short_code: string;
  original_url: string;
  custom_alias: string | null;
  expires_at: string | null;
  clicks: number;
  created_at: string;
};

export const useLinksStore = defineStore("links", {
  state: () => ({
    list: [] as LinkItem[],
    loading: false,
    error: "" as string,
    appBaseUrl: "" as string,
  }),
  actions: {
    async fetchAppConfig() {
      this.loading = true;
      this.error = "";
      try {
        const response = await api.get("/config");
        this.appBaseUrl = response.data.data.base_url;
      } catch (error) {
        this.error = "Unable to load application config.";
      } finally {
        this.loading = false;
      }
    },

    async fetchLinks() {
      this.loading = true;
      this.error = "";
      try {
        const response = await api.get("/links");
        this.list = response.data.data;
      } catch (error) {
        this.error = "Unable to load links.";
      } finally {
        this.loading = false;
      }
    },
    async createLink(payload: {
      original_url: string;
      custom_alias?: string;
      expires_at?: string;
    }) {
      this.loading = true;
      this.error = "";
      try {
        const response = await api.post("/links", payload);
        this.list.unshift(response.data.data);
      } catch (error) {
        this.error =
          error instanceof Error ? error.message : "Unable to create link.";
      } finally {
        this.loading = false;
      }
    },
    async deleteLink(code: string) {
      this.loading = true;
      this.error = "";
      try {
        await api.delete(`/links/${code}`);
        this.list = this.list.filter((item) => item.short_code !== code);
      } catch (error) {
        this.error = "Unable to delete link.";
      } finally {
        this.loading = false;
      }
    },
  },
});
