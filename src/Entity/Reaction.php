<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Repository\ReactionRepository;
use App\State\ReactionProcessor;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReactionRepository::class)]
#[ORM\UniqueConstraint(
    name: 'uniq_pub_user_type',
    columns: ['publication_id', 'author_id', 'type']
)]
#[ORM\UniqueConstraint(
    name: 'uniq_comment_user_type',
    columns: ['comment_id', 'author_id', 'type']
)]
#[ApiResource(operations: [
    new GetCollection(uriTemplate: '/{slug}/reactions', security: "is_granted('ROLE_USER')", uriVariables: [
        'slug' => new Link(
            fromClass: Workspace::class,
            identifiers: ['slug'],
            fromProperty: 'reactions'
        ),
    ],),
    new Post(uriTemplate: '/{slug}/reactions', securityPostDenormalize: "is_granted('ROLE_USER')", processor: ReactionProcessor::class, uriVariables: [
        'slug' => new Link(
            fromClass: Workspace::class,
            identifiers: ['slug'],
            fromProperty: 'reactions'
        ),
    ],),
    new Delete(uriTemplate: '/{slug}/reactions/{id}', security: "is_granted('ROLE_USER')", uriVariables: [
        'slug' => new Link(
            fromClass: Workspace::class,
            identifiers: ['slug'],
            fromProperty: 'reactions'
        ),
        'id' => new Link(fromClass: Reaction::class, identifiers: ['id']),
    ],),
])]
class Reaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reactions')]
    private ?Workspace $workspace = null;

    #[ORM\Column(length: 20)]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'reactions')]
    private ?User $author = null;

    #[ORM\ManyToOne(inversedBy: 'reactions')]
    private ?Publication $publication = null;

    #[ORM\ManyToOne(inversedBy: 'reactions')]
    private ?Comment $comment = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWorkspace(): ?Workspace
    {
        return $this->workspace;
    }

    public function setWorkspace(?Workspace $workspace): static
    {
        $this->workspace = $workspace;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getPublication(): ?Publication
    {
        return $this->publication;
    }

    public function setPublication(?Publication $publication): static
    {
        $this->publication = $publication;

        return $this;
    }

    public function getComment(): ?Comment
    {
        return $this->comment;
    }

    public function setComment(?Comment $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
