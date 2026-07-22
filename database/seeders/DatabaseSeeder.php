<?php

namespace Database\Seeders;

use App\Models\DocumentChunk;
use App\Models\Document;
use App\Models\EmbeddingCache;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Document::factory()
            ->count(5)
            ->create()
            ->each(function(Document $document){
                $chunkCount=rand(2,5);

                for($i=0;$i<$chunkCount;$i++){
                    DocumentChunk::factory()->create(
                        [
                            'document_id'=>$document->id,
                            'chunk_index'=> $i,
                        ]
                    );
                }
            });

        EmbeddingCache::factory()->count(10)->create();
    }
}
