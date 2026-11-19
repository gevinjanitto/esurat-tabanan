@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-3" aria-label="Paginasi" data-testid="pagination">
        <p class="text-xs text-slate-500">Hal. {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</p>
        <div class="flex gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="btn btn-ghost !py-2 opacity-40"><i data-lucide="chevron-left" class="w-4 h-4"></i></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-ghost !py-2" data-testid="pagination-prev"><i data-lucide="chevron-left" class="w-4 h-4"></i></a>
            @endif
            @foreach ($elements as $element)
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <a href="{{ $url }}" class="hidden sm:inline-flex btn !py-2 !px-3.5 {{ $page == $paginator->currentPage() ? 'btn-primary' : 'btn-ghost' }}">{{ $page }}</a>
                    @endforeach
                @endif
            @endforeach
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-ghost !py-2" data-testid="pagination-next"><i data-lucide="chevron-right" class="w-4 h-4"></i></a>
            @else
                <span class="btn btn-ghost !py-2 opacity-40"><i data-lucide="chevron-right" class="w-4 h-4"></i></span>
            @endif
        </div>
    </nav>
@endif
