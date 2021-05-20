<?php

/**
 * The main template file
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists
 */

namespace App;

use App\Http\Controllers\Controller;
use Rareloop\Lumberjack\Http\Responses\TimberResponse;
use Rareloop\Lumberjack\Post;
use Symfony\Component\HttpFoundation\Request;
use Timber\Timber;

class HomeController extends Controller
{
    public function handle()
    {
        $context = Timber::get_context();
        $context['posts'] = Post::all();


        $assetManager = new \SR_Asset();
        $defaultAsset = $assetManager->load_by_default(true);

        $context['defaultAsset'] = $defaultAsset;
        $context['defaultAssetCustomFields'] = $assetManager->load_custom_fields($defaultAsset->id);



        return new TimberResponse('templates/home.twig', $context);
    }
}
