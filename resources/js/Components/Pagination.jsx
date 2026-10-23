import { Link } from "@inertiajs/react";

// Les libellés de Laravel contiennent des entités HTML (« &laquo; Previous »).
const cleanLabel = (label) =>
    label.replace("&laquo;", "«").replace("&raquo;", "»");

export default function Pagination({ meta }) {
    if (!meta || meta.last_page <= 1) {
        return null;
    }

    return (
        <nav className="flex flex-wrap items-center justify-between gap-3 px-6 py-4" aria-label="Pagination">
            <p className="text-sm text-gray-500 dark:text-gray-400">
                Showing {meta.from} to {meta.to} of {meta.total} results
            </p>
            <div className="flex flex-wrap gap-1">
                {meta.links.map((link, index) =>
                    link.url ? (
                        <Link
                            key={index}
                            href={link.url}
                            preserveScroll
                            className={
                                "rounded-sm px-3 py-1 text-sm border " +
                                (link.active
                                    ? "bg-blue-600 border-blue-600 text-white"
                                    : "border-gray-300 text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700")
                            }
                        >
                            {cleanLabel(link.label)}
                        </Link>
                    ) : (
                        <span
                            key={index}
                            className="rounded-sm px-3 py-1 text-sm border border-gray-200 text-gray-400 dark:border-gray-700 dark:text-gray-500"
                        >
                            {cleanLabel(link.label)}
                        </span>
                    )
                )}
            </div>
        </nav>
    );
}
