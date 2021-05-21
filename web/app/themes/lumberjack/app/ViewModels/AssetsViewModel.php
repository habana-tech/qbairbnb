<?php
namespace App\ViewModels;

use Rareloop\Lumberjack\Post;
use Rareloop\Lumberjack\ViewModel;
use Timber\PostsCollection;

class AssetsViewModel extends ViewModel
{
    protected $asset;

    protected array $mediaCollection;
    protected array $mediaCollectionThumbUrlList = [];

    /**
     * AssetsMediaViewModel constructor.
     *
     * @param $asset
     */
    public function __construct(\stdClass $asset)
    {
        $this->asset = $asset;
        $this->mediaCollection = (new \SR_Media())->load_by_asset_id($asset->id);
    }

    public static function createFromAssetId(int $id): AssetsViewModel
    {
        return new self((new \SR_Asset())->load($id));
    }
    public static function createFromAssetSlug(string $slug): AssetsViewModel
    {
        return new self((new \SR_Asset())->load_by_alias($slug));
    }
    public static function createFromDefaultAsset(): AssetsViewModel
    {
        return new self((new \SR_Asset())->load_by_default(true));
    }

    public function name()
    {
        return $this->asset->name;
    }

    public function getMediaCollection()
    {
        return $this->mediaCollection;
    }

    public function getMediaThumbnailsUrlList()
    {
        if (count($this->mediaCollectionThumbUrlList) === count($this->mediaCollection)) {
            return $this->mediaCollectionThumbUrlList;
        }

        foreach ($this->mediaCollection as $media) {
            $this->mediaCollectionThumbUrlList[] = wp_get_attachment_image_url($media->media_id);
        }
        return $this->mediaCollectionThumbUrlList;
    }

    public function isPublished(): bool
    {
        return (int )$this->asset->state === 1;
    }
}
