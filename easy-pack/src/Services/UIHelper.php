<?php

namespace EasyPack\Services;

use Illuminate\Support\HtmlString;

class UIHelper
{
    /**
     * Generate a page headline.
     *
     * @param string $title
     * @param string|null $subtitle
     * @return HtmlString
     */
    public function pageHeadline(string $title, ?string $subtitle = null): HtmlString
    {
        $html = '<div class="d-sm-flex align-items-center justify-content-between mb-4">';
        $html .= '<h1 class="h3 mb-0 text-gray-800">' . e($title) . '</h1>';
        if ($subtitle) {
            $html .= '<p class="text-muted mb-0">' . e($subtitle) . '</p>';
        }
        $html .= '</div>';

        return new HtmlString($html);
    }

    /**
     * Generate breadcrumbs.
     *
     * @param array $items Array of [label, url, isActive]
     * @return HtmlString
     */
    public function breadcrumbs(array $items): HtmlString
    {
        $html = '<nav aria-label="breadcrumb" class="mb-4">';
        $html .= '<ol class="breadcrumb">';

        foreach ($items as $item) {
            $label = $item[0] ?? '';
            $url = $item[1] ?? null;
            $isActive = $item[2] ?? false;

            if ($isActive || $url === null) {
                $html .= '<li class="breadcrumb-item active" aria-current="page">' . e($label) . '</li>';
            } else {
                $html .= '<li class="breadcrumb-item"><a href="' . e($url) . '">' . e($label) . '</a></li>';
            }
        }

        $html .= '</ol></nav>';

        return new HtmlString($html);
    }

    /**
     * Generate an empty state panel.
     *
     * @param string $title
     * @param string|null $message
     * @param string $icon
     * @return HtmlString
     */
    public function emptyStatePanel(string $title, ?string $message = null, string $icon = 'fas fa-inbox'): HtmlString
    {
        $html = '<div class="card">';
        $html .= '<div class="card-body text-center py-5">';
        $html .= '<i class="' . e($icon) . ' fa-3x text-muted mb-3"></i>';
        $html .= '<h4>' . e($title) . '</h4>';
        if ($message) {
            $html .= '<p class="text-muted">' . e($message) . '</p>';
        }
        $html .= '</div></div>';

        return new HtmlString($html);
    }

    /**
     * Generate a metric card.
     *
     * @param string $title
     * @param int|string $value
     * @param string|null $description
     * @param string|null $route
     * @param string $icon
     * @param string $color
     * @return HtmlString
     */
    public function metricCard(
        string $title,
        $value,
        ?string $description = null,
        ?string $route = null,
        string $icon = 'fas fa-chart-bar',
        string $color = 'primary'
    ): HtmlString {
        $html = '<div class="card metric-card border-left-' . e($color) . ' h-100">';
        $html .= '<div class="card-body">';
        $html .= '<div class="row no-gutters align-items-center">';
        $html .= '<div class="col mr-2">';
        $html .= '<div class="text-xs font-weight-bold text-' . e($color) . ' text-uppercase mb-1">' . e($title) . '</div>';
        $html .= '<div class="h5 mb-0 font-weight-bold text-gray-800">' . e(is_numeric($value) ? number_format($value) : $value) . '</div>';
        if ($description) {
            $html .= '<p class="text-muted small mb-0 mt-2">' . e($description) . '</p>';
        }
        $html .= '</div>';
        $html .= '<div class="col-auto"><i class="' . e($icon) . ' fa-2x text-gray-300"></i></div>';
        $html .= '</div>';
        if ($route && \Illuminate\Support\Facades\Route::has($route)) {
            $html .= '<div class="mt-3">';
            $html .= '<a href="' . route($route) . '" class="btn btn-' . e($color) . ' btn-sm">';
            $html .= 'View Details <i class="fas fa-arrow-right ms-1"></i></a>';
            $html .= '</div>';
        }
        $html .= '</div></div>';

        return new HtmlString($html);
    }

    /**
     * Generate an alert/flash message.
     *
     * @param string $message
     * @param string $type (success, danger, warning, info)
     * @param bool $dismissible
     * @return HtmlString
     */
    public function alert(string $message, string $type = 'info', bool $dismissible = true): HtmlString
    {
        $class = 'alert alert-' . e($type);
        if ($dismissible) {
            $class .= ' alert-dismissible fade show';
        }

        $html = '<div class="' . $class . '" role="alert">';
        $html .= e($message);
        if ($dismissible) {
            $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        }
        $html .= '</div>';

        return new HtmlString($html);
    }

    /**
     * Generate a card with header.
     *
     * @param string $title
     * @param string $content
     * @param string|null $icon
     * @param string|null $headerAction HTML for action button in header
     * @return HtmlString
     */
    public function card(string $title, string $content, ?string $icon = null, ?string $headerAction = null): HtmlString
    {
        $html = '<div class="card mb-4">';
        $html .= '<div class="card-header d-flex justify-content-between align-items-center">';
        $html .= '<h6 class="m-0 font-weight-bold text-primary">';
        if ($icon) {
            $html .= '<i class="' . e($icon) . ' me-2"></i>';
        }
        $html .= e($title);
        $html .= '</h6>';
        if ($headerAction) {
            $html .= $headerAction;
        }
        $html .= '</div>';
        $html .= '<div class="card-body">' . $content . '</div>';
        $html .= '</div>';

        return new HtmlString($html);
    }

    /**
     * Check if running in sandbox/preview mode.
     *
     * @return bool
     */
    public function isSandboxMode(): bool
    {
        $host = request()->getHttpHost();
        return (bool) preg_match('/sandbox|preview|staging|demo/i', $host);
    }

    /**
     * Get environment badge HTML.
     *
     * @return HtmlString|null
     */
    public function environmentBadge(): ?HtmlString
    {
        $env = app()->environment();
        $host = request()->getHttpHost();

        if (preg_match('/sandbox/i', $host)) {
            return new HtmlString('<span class="badge bg-warning text-dark">Sandbox</span>');
        }

        if (preg_match('/preview|staging/i', $host)) {
            return new HtmlString('<span class="badge bg-info">Preview</span>');
        }

        if ($env === 'local') {
            return new HtmlString('<span class="badge bg-secondary">Local</span>');
        }

        return null;
    }
}
