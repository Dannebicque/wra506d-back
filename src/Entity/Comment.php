<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\CommentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\Index(columns: ['workspace_id', 'created_at'])]
#[ApiResource(operations: [
    new GetCollection(
        uriTemplate: '/{slug}/comments',
        uriVariables: [
            'slug' => new Link(
                fromClass: Workspace::class,
                identifiers: ['slug'],
                fromProperty: 'comments'
            ),
        ],
        security: "is_granted('ROLE_USER')"
    ),
    new Get(
        uriTemplate: '/{slug}/comments/{id}',
        uriVariables: [
            'slug' => new Link(
                fromClass: Workspace::class,
                identifiers: ['slug'],
                fromProperty: 'comments'
            ),
            'id' => new Link(fromClass: Comment::class, identifiers: ['id']),
        ],
        security: "object.getWorkspace() === service('App\\Context\\CurrentWorkspace').get()"
    ),
    new Post(
        uriTemplate: '/{slug}/comments',
        uriVariables: [
            'slug' => new Link(
                fromClass: Workspace::class,
                identifiers: ['slug'],
                fromProperty: 'comments'
            ),
        ],
        securityPostDenormalize: "is_granted('ROLE_USER')",
        processor: \App\State\CommentProcessor::class
    ),
    new Patch(
        uriTemplate: '/{slug}/comments/{id}',
        uriVariables: [
            'slug' => new Link(
                fromClass: Workspace::class,
                identifiers: ['slug'],
                fromProperty: 'comments'
            ),
            'id' => new Link(fromClass: Comment::class, identifiers: ['id']),
        ],
        securityPostDenormalize: "is_granted('ROLE_USER')"
    ),
    new Delete(
        uriTemplate: '/{slug}/comments/{id}',
        uriVariables: [
            'slug' => new Link(
                fromClass: Workspace::class,
                identifiers: ['slug'],
                fromProperty: 'comments'
            ),
            'id' => new Link(fromClass: Comment::class, identifiers: ['id']),
        ],
        security: "is_granted('ROLE_USER')"
    ),
],
    normalizationContext: ['groups' => ['comment:read']],
    denormalizationContext: ['groups' => ['comment:write']]
)]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['comment:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    private ?Workspace $workspace = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[Groups(['comment:read', 'comment:write'])]
    private ?Publication $publication = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[Groups(['comment:read'])]
    private ?User $author = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['comment:read', 'comment:write'])]
    private ?string $body = null;

    #[ORM\Column]
    #[Groups(['comment:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['comment:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Reaction>
     */
    #[ORM\OneToMany(targetEntity: Reaction::class, mappedBy: 'comment')]
    #[Groups(['comment:read'])]
    private Collection $reactions;

    /**
     * @var Collection<int, Media>
     */
    #[ORM\OneToMany(targetEntity: Media::class, mappedBy: 'comment')]
    #[Groups(['comment:read'])]
    private Collection $media;

    public function __construct()
    {
        $this->reactions = new ArrayCollection();
        $this->media = new ArrayCollection();
    }

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

    public function getPublication(): ?Publication
    {
        return $this->publication;
    }

    public function setPublication(?Publication $publication): static
    {
        $this->publication = $publication;

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

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, Reaction>
     */
    public function getReactions(): Collection
    {
        return $this->reactions;
    }

    public function addReaction(Reaction $reaction): static
    {
        if (!$this->reactions->contains($reaction)) {
            $this->reactions->add($reaction);
            $reaction->setComment($this);
        }

        return $this;
    }

    public function removeReaction(Reaction $reaction): static
    {
        if ($this->reactions->removeElement($reaction)) {
            // set the owning side to null (unless already changed)
            if ($reaction->getComment() === $this) {
                $reaction->setComment(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Media>
     */
    public function getMedia(): Collection
    {
        return $this->media;
    }

    public function addMedium(Media $medium): static
    {
        if (!$this->media->contains($medium)) {
            $this->media->add($medium);
            $medium->setComment($this);
        }

        return $this;
    }

    public function removeMedium(Media $medium): static
    {
        if ($this->media->removeElement($medium)) {
            // set the owning side to null (unless already changed)
            if ($medium->getComment() === $this) {
                $medium->setComment(null);
            }
        }

        return $this;
    }
}
