@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();

        if ($last <= 7) {
            $pages = range(1, $last);
        } elseif ($current <= 4) {
            $pages = [1, 2, 3, 4, '...', $last];
        } elseif ($current >= $last - 3) {
            $pages = [1, '...', $last - 3, $last - 2, $last - 1, $last];
        } else {
            $pages = [1, '...', $current - 1, $current, $current + 1, '...', $last];
        }
    @endphp
    <style>
        .hm-page-nav { display: flex; justify-content: center; }
        .hm-page-list { display: inline-flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 8px; list-style: none; margin: 0; padding: 0; }
        .hm-page-btn { display: inline-flex; align-items: center; justify-content: center; box-sizing: border-box; width: 40px; height: 40px; border: 1px solid #c5c5c5; border-radius: 4px; background: #fff; color: #333; font-size: 14px; line-height: 1; text-decoration: none; }
        .hm-page-btn:hover { background: #f7f7f7; }
        .hm-page-btn.is-current { background: #000; border-color: #000; color: #fff; }
        .hm-page-gap { display: inline-flex; align-items: center; height: 40px; padding: 0 4px; color: #6b7280; font-size: 14px; letter-spacing: 0.08em; }
    </style>
    <nav role="navigation" aria-label="ページ" class="hm-page-nav">
        <ul class="hm-page-list">
            @if (! $paginator->onFirstPage())
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="前のページ" class="hm-page-btn">&lt;</a>
                </li>
            @endif

            @foreach ($pages as $page)
                <li>
                    @if ($page === '...')
                        <span class="hm-page-gap" aria-hidden="true">...</span>
                    @elseif ($page === $current)
                        <span aria-current="page" class="hm-page-btn is-current">{{ $page }}</span>
                    @else
                        <a href="{{ $paginator->url($page) }}" aria-label="{{ $page }}ページ" class="hm-page-btn">{{ $page }}</a>
                    @endif
                </li>
            @endforeach

            @if ($paginator->hasMorePages())
                <li>
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="次のページ" class="hm-page-btn">&gt;</a>
                </li>
            @endif
        </ul>
    </nav>
@endif
