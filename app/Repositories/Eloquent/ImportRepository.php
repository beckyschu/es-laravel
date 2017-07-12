<?php

namespace App\Repositories\Eloquent;

use App\Jobs;
use App\Models;
use App\Contracts;
use App\Repositories;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Illuminate\Support\Facades\Log;

class ImportRepository implements Contracts\ImportRepositoryInterface
{
	private $columns_match = [
				'title' => ['resultsproductname', 'name', 'listingtitle'],
				'url' => ['resultsproducturl', 'namehref', 'site', 'productlistingurl'],
				'price' => ['resultsproductsellersprice', 'resultsproductprice', 'price'],
				'seller' => ['resultsproductsellersname', 'resultsproductseller', 'seller'],
				'origin' => ['resultsproductlocation', 'location'],
				'sku' => ['resultsproductitemnumber', 'itemnumber'],
				'picture' => ['resultsproductimageurl', 'imagesrc', 'imageurl'],
				'qtyavailable' => ['qtyavailable'],
				'qtysold' => ['qtysold'],
				'status' => ['status'],
				'created_at' => ['createdat', 'datecreated'],
				'last_seen_at' => ['lastseenat', 'lastseen'],
				'platform' => ['platform'],
				'keyword' => ['resultskeyword', 'keyword', 'searchterm']
		];
	private $columns = [];

	public function __construct(){
		foreach($this->columns_match as $k => $v){
			$this->columns[$k] = [];
		}
	}

    private function get_column_match($row){
		foreach($row as $i => $cell){
			$cell_str = '';
			for($j = 0; $j < strlen($cell); ++$j){
				$l_c = strtolower($cell[$j]);
				if(($l_c >= 'a' && $l_c <= 'z') || ($l_c >= '0' && $l_c <= '9')){
					$cell_str .= $l_c;
				}
			}
			foreach($this->columns_match as $k => $v){
				$index = array_search($cell_str, $v);
				if($index !== FALSE){
					$this->columns[$k][$index] = $i;
				}
			}
		}
		foreach($this->columns as $i => $v){
			ksort($this->columns[$i]);
		}
	}

	private function get_value($row, $key){
		$v = null;
		foreach($this->columns[$key] as $index){
			$v = trim($row[$index]);
			if($v === '')
				$v = null;
			if($v !== null){
				break;
			}
		}
		return $v;
	}

    public function find($id)
    {
        return Models\Import::findOrFail($id);
    }

    public function validate_file($file){
        // Uploaded file is not a CSV
        if ('csv' !== $file->getClientOriginalExtension()) {
            throw new BadRequestHttpException('Only CSV files are accepted for import.');
        }

        // Move the temporary file into a storage location
        $file = $file->move(storage_path('app/private/imports_tmp'), 'import_'.str_random(10).'.csv');

        $csv = \League\Csv\Reader::createFromPath($file->getPathname());
				$first_row = $csv->fetchOne();
				if($first_row){
					$this->get_column_match($first_row);



				}

				Log::info('[Repositories\ImportRepository] Import: first_row '. json_encode($this->columns));

				$keyword_list = [];
				if(array_key_exists('keyword', $this->columns) && !empty($this->columns['keyword'])){
					$csv->setOffset(1);
					$csv->each(function ($row) use (&$keyword_list){

						$keyword = strtolower($this->get_value($row, 'keyword'));

						if(array_key_exists($keyword, $keyword_list)){
							$keyword_list[$keyword]['count']++;
						}
						else{
							$asset = app('App\Contracts\AssetRepositoryInterface')->findByKeyword($keyword);
							$keyword_list[$keyword] = ['found' => $asset !== null, 'count' => 1];
						}
						return true;
					});
				}
				return $keyword_list;

    }

    /**
     * Import the provided CSV file.
     *
     * @param  string                       $platform
     * @param  Illuminate\Http\UploadedFile $file
     * @return void
     */
    public function import($platform, \Illuminate\Http\UploadedFile $file)
    {
        // Uploaded file is not a CSV
        if ('csv' !== $file->getClientOriginalExtension()) {
            throw new BadRequestHttpException('Only CSV files are accepted for import.');
        }

        // Start a new import
        $import = Models\Import::create([
            'platform' => "multiple",
            'status'   => 'processing'
        ]);

        // Move the temporary file into a storage location
        $file = $file->move(storage_path('app/private/imports'), 'import_'.$import->id.'.csv');

        // Crack open the CSV
        $csv = \League\Csv\Reader::createFromPath($file->getPathname());

        // Not mine, unsure - Dan
		    $first_row = $csv->fetchOne();
	    	if ($first_row){
	    		$this->get_column_match($first_row);
	    	}

        // No keyword column found
        if (! array_key_exists('keyword', $this->columns) || empty($this->columns['keyword'])) {
            throw new \Exception('No keyword column found in the file.');
        }

        // Ignore the CSV header
        $csv->setOffset(1);

        // Instantiate discovery repo
        $discoveriesRepo = app(Repositories\Eloquent\DiscoveryRepository::class);

        // Instantiate keywords found array
        $keywords_found = [];

        // Loop CSV rows and create discoveries
        $csv->each(function ($row) use ($discoveriesRepo, $import, &$keywords_found)
        {
            $keyword = strtolower($this->get_value($row, 'keyword'));

    		if (! array_key_exists($keyword, $keywords_found)) {
    			$asset = app(Repositories\Eloquent\AssetRepository::class)->findByKeyword($keyword);
    			$keywords_found[$keyword] = $asset !== null;
    		}

            if ($keywords_found[$keyword])
            {
                $url = $this->get_value($row, 'url');
                if ($url === null) return true;

                $picture = $this->get_value($row, 'picture');

								//Log::info('[Repositories\ImportRepository] Import: created_at '. $this->get_value($row, 'created_at'));
								//Log::info('[Repositories\ImportRepository] Title: '.$this->get_value($row, 'title'). 'Price: '.$this->get_value($row, 'price'));
								// Clean Title
                $cleanTitle = iconv("utf-8", "utf-8//ignore", utf8_encode($this->get_value($row, 'title')));
								// Clean Price
                $cleanPrice = iconv("utf-8", "utf-8//ignore", utf8_encode($this->get_value($row, 'price')));

								//Log::info('[Repositories\ImportRepository] Title: '.$this->get_value($row, 'title'). 'Price: '.$this->get_value($row, 'price'));
								//Log::info('[Repositories\ImportRepository] Title: '.$cleanTitle. 'Price: '.$cleanPrice);

								// Trim URL if too large

								$urlTrimmed = (strlen($url) > 254) ? substr($url, 0, 254) : $url;

                $discoveriesRepo->discover([
                    'title'         => $cleanTitle,
                    'url'           => $urlTrimmed,
                    'price'         => $cleanPrice,
                    'seller'        => $this->get_value($row, 'seller'),
                    'origin'        => $this->get_value($row, 'origin'),
                    'sku'           => $this->get_value($row, 'sku'),
                    'picture'       => (stripos($picture, 'http://') === 0 || stripos($picture, 'https://') === 0) ? $picture : 'http://'.$picture,
                    'qty_available' => $this->get_value($row, 'qtyavailable'),
                    'qty_sold'      => $this->get_value($row, 'qtysold'),
										'status'      	=> strtolower($this->get_value($row, 'status')),
										'created_at'    => strtotime($this->get_value($row, 'created_at')),
										'last_seen_at'  => strtotime($this->get_value($row, 'last_seen_at')),
										'platform'      	=> strtolower($this->get_value($row, 'platform')),
                    'keyword'       => $keyword
                ], $import);
            }

            return true;
        });

        $this->update($import, ['status' => 'complete']);
    }

    public function update(Models\Import $import, $attributes)
    {
        $import->fill($attributes);
        $import->save();
    }

    /**
     * Increment the provided column.
     *
     * @param  Models\Import $import
     * @param  string        $column
     * @return void
     */
    public function increment(Models\Import $import, $column)
    {
        // Increment provided column
        \DB::table($import->getTable())->where('id', $import->id)->increment($column);
    }
}
