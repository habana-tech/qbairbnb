<?php


namespace App\Http\Controllers;


use App\PostTypes\Asset;
use App\ViewModels\AssetsViewModel;
use Rareloop\Lumberjack\Http\Controller as BaseController;
use Rareloop\Lumberjack\Http\Responses\RedirectResponse;
use Rareloop\Lumberjack\Http\Responses\TimberResponse;
use Rareloop\Lumberjack\Post;
use Timber\Timber;

class AssetController extends BaseController {

    /**
     * @throws \Rareloop\Lumberjack\Exceptions\TwigTemplateNotFoundException
     */
    public function show($slug)
    {
        $context = Timber::get_context();
        $asset = AssetsViewModel::createFromAssetSlug($slug);
        dump($asset->isPublished());
        if(!$asset->isPublished()){
            return (new RedirectResponse('/'))->with('error', 'Something went wrong');
        }
        dump($asset);
        $context['asset'] = $asset;
        return new TimberResponse('templates/asset.twig', $context);
    }
}
