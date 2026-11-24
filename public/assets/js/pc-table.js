/**
 * pc-table.js — reusable table helpers shared by every page with a search/sort/filter/paginate
 * table (client-side lists). Pure functions, no DOM access.
 * JS API: pcTable.filterBySearch(list, fields, query) / sortBy(list, key, dir, accessor)
 *         / paginate(list, page, perPage) / sortIcon(currentKey, key, dir)
 */
(function () {
  function filterBySearch(list, fields, query) {
    const q = String(query || "").trim().toLowerCase();
    if (!q) return list;
    return list.filter((item) =>
      fields.map((f) => String(item[f] ?? "")).join(" ␟ ").toLowerCase().includes(q)
    );
  }

  function sortBy(list, key, dir, accessor) {
    const get = accessor || ((item) => item[key]);
    return [...list].sort((a, b) => {
      const av = get(a);
      const bv = get(b);
      if (typeof av === "string" || typeof bv === "string") {
        return String(av).localeCompare(String(bv), undefined, { numeric: true }) * dir;
      }
      return (Number(av) - Number(bv)) * dir;
    });
  }

  function paginate(list, page, perPage) {
    const total = list.length;
    const totalPages = Math.max(1, Math.ceil(total / perPage));
    const clampedPage = Math.min(Math.max(1, page), totalPages);
    const start = (clampedPage - 1) * perPage;
    return {
      rows: list.slice(start, start + perPage),
      page: clampedPage,
      totalPages,
      total,
      start,
      end: Math.min(start + perPage, total),
    };
  }

  function sortIcon(currentKey, key, dir) {
    if (currentKey !== key) return "⇅";
    return dir === 1 ? "↑" : "↓";
  }

  window.pcTable = { filterBySearch, sortBy, paginate, sortIcon };
})();
