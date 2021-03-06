<?php

namespace App\Manager;

use App\App;
use App\Entity\PostEntity;
use App\Entity\UserEntity;
use App\Trait\PostTrait;
use Core\Database\QueryBuilder;
use Core\Manager\EntityManager;
use DateTime;
use Exception;

class PostManager extends EntityManager
{
    use PostTrait;

    /**
     * Retourne tous les articles publiés, ordonné du plus récent au plus ancien
     * @throws Exception
     */
    public function findAllPublishedOrderedByNewest(): ?array
    {
        $statement = $this->getAllPublishedOrderedByNewest()->getQuery();
        $postsData = $this->query($statement);

        return $this->createPostsWithAuthor($postsData);
    }

    /**
     * Retourne un unique article identifié à partir de son id, avec ses commentaires approuvés
     * @throws Exception
     */
    public function findOneByIdWithCommentsApproved(int $id): ?PostEntity
    {
        $statement = $this->getOneByIdWithCommentsApproved()->getQuery();
        $postData = $this->prepare($statement, [':id' => $id], false, false);
        return $this->createPostWithAuthorAndComments($postData);
    }

    /**
     * Retourne un unique article publié identifié à partir de son id
     * @throws Exception
     */
    public function findOnePublishedById(int $id): ?PostEntity
    {
        $statement = $this->getOnePublishedById()->getQuery();
        $postData = $this->prepare($statement, [':id' => $id], true, false);

        return $this->createPostWithAuthor($postData);
    }

    /**
     * Retourne un unique article identifié à partir de son id
     * @throws Exception
     */
    public function findOneById(int $id): ?PostEntity
    {
        $statement = $this->getOneById()->getQuery();
        $postData = $this->prepare($statement, [':id' => $id], true, false);

        return $this->createPostWithAuthor($postData);
    }

    /**
     * Retourne un unique article identifié à partir de son id si l'utilisateur est l'auteur
     * @throws Exception
     */
    public function findOneByIdIfIsGranted(int $id, UserEntity $user): ?PostEntity
    {
        $qb = $this->getOneByIdWithComments();

        $attributs = [
            ':id' => $id,
        ];


        if ($user->getRole() !== UserEntity::ROLE_SUPERADMIN) {
            $qb->andWhere('p.author = :author');
            $attributs = array_merge($attributs, [':author' => $user->getId()]);
        }

        $statement = $qb->getQuery();
        $postData = $this->prepare($statement, $attributs);

        if (!$postData) {
            throw new Exception(App::$config['ADMIN_POST_UPDATE_ERROR_MESSAGE']);
        }
        return $this->createPostWithAuthorAndComments($postData);
    }

    /**
     * Retourne tous les articles d'un auteur, ordonné du plus récent au plus ancien
     * @throws Exception
     */
    public function findAllOrderedByNewest(UserEntity $user): ?array
    {
        $qb = $this->getAllOrderedByNewest();

        $attributs = [];
        if ($user->getRole() !== UserEntity::ROLE_SUPERADMIN) {
            $qb->andWhere('a.id = :id');
            $attributs = array_merge($attributs, [':id' => $user->getId()]);
        }

        $statement = $qb->getQuery();
        $postsData = $this->prepare($statement, $attributs);

        return $this->createPostsWithAuthor($postsData);
    }

    /**
     * Suppression d'un article
     * @param PostEntity $post
     * @param UserEntity $user
     *
     * @return bool
     */
    public function delete(PostEntity $post, UserEntity $user): bool
    {
        $qb = $this->createQueryBuilder()
                    ->delete('post')
                    ->where('id = :id')
        ;

        $attributs = [':id' => $post->getId()];

        if ($user->getRole() !== UserEntity::ROLE_SUPERADMIN) {
            $qb->andWhere('author = :author');
            $attributs = array_merge($attributs, [':author' => $user->getId()]);
        }

        $statement = $qb->getQuery();
        return $this->execute($statement, $attributs);
    }

    /**
     * Mise à jour d'un article
     * @param PostEntity $post
     * @param bool       $isPublishedChange
     *
     * @return bool
     */
    public function update(PostEntity $post, bool $isPublishedChange): bool
    {
        $qb = $this->createQueryBuilder()
            ->update('post', 'p')
            ->set('p.title = :title', 'p.header = :header', 'p.content = :content', 'p.published = :published', 'p.updated_at = :updated_at')
            ->where('p.id = :id')
        ;

        $attributs = [
            ':id'           => $post->getId(),
            ':title'        => $post->getTitle(),
            ':header'       => $post->getHeader(),
            ':content'      => $post->getContent(),
            ':published'    => $post->isPublished(),
            ':updated_at'   => (new DateTime())->format('Y-m-d H:i:s')
        ];

        if ($isPublishedChange) {
            $qb->addSet('p.published_at = :published_at');

            if ($post->isPublished()) {
                $publishedAt = [':published_at' => (new DateTime())->format('Y-m-d H:i:s')];
            } else {
                $publishedAt = [':published_at' => null];
            }

            $attributs = array_merge($attributs, $publishedAt);
        }

        $statement = $qb->getQuery();

        return $this->execute($statement, $attributs);
    }

    /**
     * Création d'un nouvel article
     * @param PostEntity $post
     *
     * @return bool
     */
    public function new(PostEntity $post): bool
    {
        $qb = $this->createQueryBuilder()
               ->insert('post')
               ->columns('title', 'header', 'content', 'published', 'author')
        ;

        $attributs = [
            ':title'        => $post->getTitle(),
            ':header'       => $post->getHeader(),
            ':content'      => $post->getContent(),
            ':published'    => $post->isPublished(),
            ':author'       => $post->getAuthor()->getId()
        ];

        if ($post->isPublished()) {
            $qb->addColumns('published_at');
            $publishedAt = [':published_at' => (new DateTime())->format('Y-m-d H:i:s')];
            $attributs = array_merge($attributs, $publishedAt);
        }

        $statement = $qb->getQuery();
        return $this->execute($statement, $attributs, true);
    }

    //--------------------------------------------------------------
    //------- Query Builder
    //--------------------------------------------------------------

    /**
     * Retourne le QB de tous les articles
     */
    private function getAll(): QueryBuilder
    {
        return $this->createQueryBuilder()
                    ->select('p.id', 'p.title', 'p.header', 'p.content', 'p.author', 'p.published')
                    ->addSelect('p.published_at as publishedAt', 'p.created_at as createdAt', 'p.updated_at as updatedAt')
                    ->addSelect('a.username')
                    ->from('post', 'p')
                    ->innerJoin('user', 'a', 'p.author = a.id')
            ;
    }

    /**
     * Retourne le QB de tous les articles publiés
     */
    private function getAllPublished(): QueryBuilder
    {
        return $this->getAll()
            ->andWhere('p.published IS TRUE')
        ;
    }

    /**
     * Retourne le QB de tous les article publiés, ordonnés du plus récent au plus ancien
     */
    private function getAllPublishedOrderedByNewest(): QueryBuilder
    {
        return $this->getAllPublished()
            ->addCount('c.id', 'comments', 'p.id')
            ->leftJoin('comment', 'c', 'c.post = p.id', 'c.approved IS TRUE')
            ->orderBy('p.published_at', 'DESC')
        ;
    }

    /**
     * Retourne le QB d'un unique article publié identifié à partir de son id
     */
    private function getOnePublishedById(): QueryBuilder
    {
        return $this->getAllPublished()
            ->andWhere('p.id = :id')
        ;
    }

    /**
     * Retourne le QB d'un unique article identifié à partir de son id
     */
    private function getOneById(): QueryBuilder
    {
        return $this->getAll()
                ->andWhere('p.id = :id')
        ;
    }

    /**
     * Retourne le QB d'un unique article identifié à partir de son id
     */
    private function getOneByIdWithComments(): QueryBuilder
    {
        return $this->getAll()
                    ->addSelect('c.id as commentId', 'c.content as commentContent', 'c.created_at as commentCreatedAt', 'c.username as commentUsername', 'c.approved')
                    ->leftJoin('comment', 'c', 'c.post = p.id')
                    ->orderBy('c.created_at', 'DESC')
                    ->andWhere('p.id = :id')
            ;
    }

    /**
     * Retourne le QB d'un unique article identifié à partir de son id
     */
    private function getOneByIdWithCommentsApproved(): QueryBuilder
    {
        return $this->getOnePublishedById()
            ->addSelect('c.id as commentId, c.content as commentContent, c.created_at as commentCreatedAt, c.username as commentUsername')
            ->leftJoin('comment', 'c', 'c.post = p.id', 'c.approved IS TRUE')
            ->orderBy('c.created_at', 'DESC')
        ;
    }

    /**
     * Retourne le QB d'un unique article identifié à partir de son id
     */
    private function getAllOrderedByNewest(): QueryBuilder
    {
        return $this->getAll()
            ->addCount('c.approved', 'comments', 'p.id')
            ->addSelect('sum(c.approved) as commentsApproved')
            ->leftJoin('comment', 'c', 'c.post = p.id')
        ;
    }
}
