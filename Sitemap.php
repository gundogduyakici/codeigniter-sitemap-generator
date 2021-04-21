<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Sitemap extends CI_Controller
{
    
    protected $limit = 5;
    
    public function __construct()
    {
        parent::__construct();
        $this->limit = $limit;
    }

    public function index()
    {
        $query = $this
            ->db
            ->query("SELECT * FROM table_name");

        if (!$totalCache = $this
            ->cache
            ->file
            ->get('totalCache'))
        {
            $totalCache = $query->num_rows();
            $this
                ->cache
                ->file
                ->save('totalCache', $totalCache, 86400);
        }

        if (!$queryCache = $this
            ->cache
            ->file
            ->get('queryCache'))
        {
            $queryCache = $query->result();

            $this
                ->cache
                ->file
                ->save('queryCache', $queryCache, 86400);
        }

        header('Content-type: application/xml');

        $totalItems = $totalCache;
        $totalPages = ceil($totalItems / $this->limit);

        $data['items'] = $queryCache;
        $data['type'] = 'index';
        $data['date'] = mdate("%Y-%m-%d %h:%i");
        $data['pages'] = $totalPages;

        $this
            ->load
            ->view('sitemap', $data);
    }

    public function listing()
    {
        $page = ($this
            ->uri
            ->segment(2) < 1) ? 1 : $this
            ->uri
            ->segment(2);

        for ($i = 1;$i <= $page;$i++)
        {
            $offset = ($i - 1) * $this->limit;
            $rows = $this
                ->db
                ->query("SELECT * FROM table_name ORDER BY id ASC LIMIT $offset, $this->limit");

            if (!$rowsCache[$i] = $this
                ->cache
                ->file
                ->get('rowsCache' . $i))
            {
                $rowsCache[$i] = $rows->result();
                $this
                    ->cache
                    ->file
                    ->save('rowsCache' . $i, $rowsCache[$i], 86400);
            }

            if (!$rowsCache[$i])
            {
                $this
                    ->cache
                    ->file
                    ->delete('rowsCache' . $i);
            }

            $listings = array();
            foreach ($rowsCache[$i] as $row)
            {
                $listings[] = array(
                    'https://www.domain.com/test',
                    $row->updated_at
                );
            }

            $data['listings'][$i] = $listings;
        }

        if (!$listings)
        {
            show_404();
        }

        $data['type'] = 'listing';

        header('Content-type: application/xml');
        $this
            ->load
            ->view('sitemap', $data);
    }
}
