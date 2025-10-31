<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\PublicationRepository;
use App\State\PublicationProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PublicationRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['publication:read']],
    denormalizationContext: ['groups' => ['publication:write']],
    operations: [
    new GetCollection(
        uriTemplate: '/{slug}/publications',
        uriVariables: [
            // "slug" n'est pas un identifiant de Publication, on le mappe via Workspace.slug
            'slug' => new Link(
                fromClass: Workspace::class,
                identifiers: ['slug'],
                fromProperty: 'publications'
            ),
        ],
        security: "is_granted('ROLE_USER')"
    ),
    new Get(
        uriTemplate: '/{slug}/publications/{id}',
        uriVariables: [
            'slug' => new Link(
                fromClass: Workspace::class,
                identifiers: ['slug'],
                fromProperty: 'publications'
            ),
            'id'   => new Link(fromClass: Publication::class, identifiers: ['id']),
        ],
        security: "object.getWorkspace() === service('App\\Context\\CurrentWorkspace').get()"
    ),
    new Post(
        uriTemplate: '/{slug}/publications',
        uriVariables: [
            'slug' => new Link(
                fromClass: Workspace::class,
                identifiers: ['slug'],
                fromProperty: 'publications'
            ),
        ],
        read: false,
        securityPostDenormalize: "is_granted('ROLE_USER')",
        processor: PublicationProcessor::class
    ),
    new Patch(
        uriTemplate: '/{slug}/publications/{id}',
        uriVariables: [
            'slug' => new Link(
                fromClass: Workspace::class,
                identifiers: ['slug'],
                fromProperty: 'publications'
            ),
            'id'   => new Link(fromClass: Publication::class, identifiers: ['id']),
        ],
        securityPostDenormalize: "is_granted('ROLE_USER')",
        processor: PublicationProcessor::class
    ),
    new Delete(
        uriTemplate: '/{slug}/publications/{id}',
        uriVariables: [
            'slug' => new Link(
                fromClass: Workspace::class,
                identifiers: ['slug'],
                fromProperty: 'publications'
            ),
            'id'   => new Link(fromClass: Publication::class, identifiers: ['id']),
        ],
        security: "is_granted('ROLE_USER')"
    ),
])]
class Publication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['publication:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'publications')]
    #[Groups(['publication:read'])]
    private ?Workspace $workspace = null;

    #[ORM\ManyToOne(inversedBy: 'publications')]
    #[Groups(['publication:read'])]
    private ?User $author = null;

    #[ORM\ManyToOne(inversedBy: 'publications')]
    #[Groups(['publication:read', 'publication:write'])]
    private ?Channel $channel = null;

    #[ORM\Column(length: 255)]
    #[Groups(['publication:read', 'publication:write'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['publication:read', 'publication:write'])]
    private ?string $body = null;

    #[ORM\Column]
    #[Groups(['publication:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['publication:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'publication')]
    private Collection $comments;

    /**
     * @var Collection<int, Reaction>
     */
    #[ORM\OneToMany(targetEntity: Reaction::class, mappedBy: 'publication')]
    private Collection $reactions;

    /**
     * @var Collection<int, Media>
     */
    #[ORM\OneToMany(targetEntity: Media::class, mappedBy: 'publication')]
    private Collection $media;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->reactions = new ArrayCollection();
        $this->media = new ArrayCollection();

        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getChannel(): ?Channel
    {
        return $this->channel;
    }

    public function setChannel(?Channel $channel): static
    {
        $this->channel = $channel;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

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
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setPublication($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getPublication() === $this) {
                $comment->setPublication(null);
            }
        }

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
            $reaction->setPublication($this);
        }

        return $this;
    }

    public function removeReaction(Reaction $reaction): static
    {
        if ($this->reactions->removeElement($reaction)) {
            // set the owning side to null (unless already changed)
            if ($reaction->getPublication() === $this) {
                $reaction->setPublication(null);
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
            $medium->setPublication($this);
        }

        return $this;
    }

    public function removeMedium(Media $medium): static
    {
        if ($this->media->removeElement($medium)) {
            // set the owning side to null (unless already changed)
            if ($medium->getPublication() === $this) {
                $medium->setPublication(null);
            }
        }

        return $this;
    }

    public function setId(null $null)
    {
        $this->id = $null;
    }
}
