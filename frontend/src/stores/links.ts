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
  is_active: boolean;
};

type PaginationData = {
  limit: number;
  offset: number;
  total: number;
  has_more: boolean;
};

export const useLinksStore = defineStore("links", {
  state: () => ({
    list: [] as LinkItem[],
    loading: false,
    error: "" as string,
    appBaseUrl: "" as string,
    pagination: null as PaginationData | null,
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

    async fetchLinks(params?: {
      limit?: number;
      offset?: number;
      status?: string;
      sortBy?: string;
      sortOrder?: string;
    }) {
      this.loading = true;
      this.error = "";
      try {
        const queryParams = new URLSearchParams();
        if (params?.limit) queryParams.append("limit", params.limit.toString());
        if (params?.offset)
          queryParams.append("offset", params.offset.toString());
        if (params?.status) queryParams.append("status", params.status);
        if (params?.sortBy) queryParams.append("sort", params.sortBy);
        if (params?.sortOrder) queryParams.append("order", params.sortOrder);

        const url = queryParams.toString() ? `/links?${queryParams}` : "/links";
        const response = await api.get(url);
        this.list = response.data.data;
        this.pagination = response.data.pagination || null;
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
    async toggleActive(code: string) {
      this.loading = true;
      this.error = "";
      try {
        const link = this.list.find((item) => item.short_code === code);
        if (!link) return;

        const newActive = !link.is_active;
        await api.put(`/links/${code}`, { is_active: newActive });
        link.is_active = newActive;
      } catch (error) {
        this.error = "Unable to update link status.";
      } finally {
        this.loading = false;
      }
    },
    async permanentlyDelete(code: string) {
      this.loading = true;
      this.error = "";
      try {
        await api.delete(`/links/${code}?permanent=1`);
        this.list = this.list.filter((item) => item.short_code !== code);
      } catch (error) {
        this.error = "Unable to permanently delete link.";
      } finally {
        this.loading = false;
      }
    },
  },
});
