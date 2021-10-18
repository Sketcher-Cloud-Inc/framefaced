<?php
namespace Tests;

class Databases {

    private \System\Databases $dbengine;

    public function __construct(
        private bool $crash
    ){
        echo "Creation of the database structure and insertion of samples datas... (this operation may take a few seconds)\n";
        foreach ($this->ScanDumpData() as $database => $collections) {
            echo "\n---------- Switching database to \"$database\" ----------\n";
            $this->dbengine = new \System\Databases($database); 
            foreach ($collections as $collection => $data) {
                echo "Deletion of existing collection data in \"$collection\"...\n";
                $this->dbengine->deleteMany($collection, [], $this->dbengine->countDocuments($collection));
                echo "Inserting dump data in \"$collection\"...\n";
                $this->dbengine->insertMany($collection, $data);
            }
        }
    }

    /**
     * Parse generated json dump
     *
     * @param string|null $subdir
     * @return array
     */
    private function ScanDumpData(?string $subdir = null): array {
        $dump = [];
        $path = __path__ . "/src/Tests/dump/{$subdir}";
        foreach (scandir($path) as $scan) {
            if ($scan !== "." && $scan !== "..") {
                if (is_dir("{$path}/{$scan}")) {
                    $dump = array_merge($dump, $this->ScanDumpData($scan));
                } else {
                    $db = explode("/", str_replace("\\", "/", $path));
                    [ $database, $collection ] = [ $db[count($db) - 1], basename($scan, ".json") ]; 
                    $data = json_decode(file_get_contents("{$path}/{$scan}"), false);
                    $dump[$database][$collection] = $data;
                }
            }
        }
        return $dump;
    }

}