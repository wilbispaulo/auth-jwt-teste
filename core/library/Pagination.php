<?php

namespace core\library;

class Pagination
{

    private int $currentPage = 1;
    private int $totalPages;
    private int $linksPerPage = 2;
    private int $itensPerPage = 10;
    private int $totalItens;
    private string $pageIdTxt = 'page';

    public function setTotalItens(int $value)
    {
        $this->totalItens = $value;
    }

    public function setPageIdTxt(string $value)
    {
        $this->pageIdTxt = $value;
    }

    public function setItensPerPage(int $value)
    {
        $this->itensPerPage = $value;
    }

    public function getTotal()
    {
        return $this->totalItens;
    }

    public function getPerPage()
    {
        return $this->itensPerPage;
    }

    private function calculations()
    {
        $this->currentPage = $_GET[$this->pageIdTxt] ?? 1;

        $offset = ($this->currentPage - 1) * $this->itensPerPage;

        $this->totalPages = intdiv($this->totalItens, $this->itensPerPage);
        $this->totalItens % $this->itensPerPage ? $this->totalPages++ : $this->totalPages;

        return " limit {$this->itensPerPage} offset {$offset}";
    }

    public function dump()
    {
        return $this->calculations();
    }
    // * * = * *
    // 1 2 3 
    public function links()
    {
        $links = "<ul class='pagination'>";
        if ($this->currentPage > 1) {
            $previus = $this->currentPage - 1;
            $linkPage = http_build_query(array_merge($_GET, [$this->pageIdTxt => $previus]));
            $first = http_build_query(array_merge($_GET, [$this->pageIdTxt => 1]));
            $links .= "<li class='page-item'><a class='page-link' href='?{$first}'>Primeira</a></li>";
            $links .= "<li class='page-item'><a class='page-link' href='?{$linkPage}'>Anterior</a></li>";
        }

        $linksShow = $this->linksPerPage * 2 + 1;
        $linkStart = ($this->currentPage - $this->linksPerPage < 1) ? 1 : $this->currentPage - $this->linksPerPage;
        $linkStart = ($this->currentPage + $this->linksPerPage > $this->totalPages) ? $this->totalPages - $linksShow + 1 : $linkStart;
        $linkStart = ($linksShow > $this->totalPages) ? 1 : $linkStart;
        $linkEnd = min($linkStart + $linksShow - 1, $this->totalPages);

        for ($i = $linkStart; $i <= $linkEnd; $i++) {
            $active = ($this->currentPage === $i) ? " active' aria-current='page'" : "'";
            $linkPage = http_build_query(array_merge($_GET, [$this->pageIdTxt => $i]));
            $links .= "<li class='page-item{$active}><a class='page-link' href='?{$linkPage}'>$i</a></li>";
        }

        if ($this->currentPage < $this->totalPages) {
            $next = $this->currentPage + 1;
            $linkPage = http_build_query(array_merge($_GET, [$this->pageIdTxt => $next]));
            $last = http_build_query(array_merge($_GET, [$this->pageIdTxt => $this->totalPages]));
            $links .= "<li class='page-item'><a class='page-link' href='?{$linkPage}'>Próxima</a></li>";
            $links .= "<li class='page-item'><a class='page-link' href='?{$last}'>Última</a></li>";
        }

        $links .= "</ul>";

        return $links;
    }
}
