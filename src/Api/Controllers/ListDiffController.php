<?php

namespace IanM\Diff\Api\Controllers;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\UrlGenerator;
use Psr\Http\Message\ServerRequestInterface;
use IanM\Diff\Api\Serializers\DiffSerializer;
use IanM\Diff\Repositories\DiffRepository;
use Illuminate\Support\Arr;
use Tobscure\JsonApi\Document;

class ListDiffController extends AbstractListController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = DiffSerializer::class;

    /**
     * {@inheritdoc}
     */
    public $include = ['actor', 'deletedUser', 'rollbackedUser'];

    /**
     * {@inheritdoc}
     */
    public $limit = 10;

    /**
     * @var DiffRepository
     */
    protected $diff;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param DiffRepository $diff
     * @param UrlGenerator   $url
     */
    public function __construct(DiffRepository $diff, UrlGenerator $url)
    {
        $this->diff = $diff;
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = $request->getAttribute('actor');

        $actor->assertCan('viewEditHistory');

        $postId = Arr::get($request->getQueryParams(), 'id');

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        $include = $this->extractInclude($request);

        $diff = $this->diff->findWhere(
            ['post_id' => $postId],
            ['revision' => 'DESC'],
            $limit + 1,
            $offset
        )
            ->load($include)
            ->all();

        $areMoreResults = false;

        if (count($diff) > $limit) {
            array_pop($diff);
            $areMoreResults = true;
        }

        $document->addPaginationLinks(
            $this->url->to('api')->route('diff.index', ['id' => $postId]),
            $request->getQueryParams(),
            $offset,
            $limit,
            $areMoreResults ? null : 0
        );

        return $diff;
    }
}
