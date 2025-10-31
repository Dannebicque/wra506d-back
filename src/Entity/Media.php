<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Repository\MediaRepository;
use App\State\MediaProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MediaRepository::class)]
#[ApiResource(operations: [
    new GetCollection(uriTemplate: '/{slug}/media', security: "is_granted('ROLE_USER')", uriVariables: [
        'slug' => new Link(
            fromClass: Workspace::class,
            identifiers: ['slug'],
            fromProperty: 'media'
        ),
    ],),
    new Get(uriTemplate: '/{slug}/media/{id}', security: "object.getWorkspace() === service('App\\Context\\CurrentWorkspace').get()", uriVariables: [
        'slug' => new Link(
            fromClass: Workspace::class,
            identifiers: ['slug'],
            fromProperty: 'media'
        ),
        'id' => new Link(fromClass: Media::class, identifiers: ['id']),
    ],),
    new Post(uriTemplate: '/{slug}/media', securityPostDenormalize: "is_granted('ROLE_USER')", processor: MediaProcessor::class, uriVariables: [
        'slug' => new Link(
            fromClass: Workspace::class,
            identifiers: ['slug'],
            fromProperty: 'media'
        ),
    ],),
    new Delete(uriTemplate: '/{slug}/media/{id}', security: "is_granted('ROLE_USER')", uriVariables: [
        'slug' => new Link(
            fromClass: Workspace::class,
            identifiers: ['slug'],
            fromProperty: 'comments'
        ),
        'id' => new Link(fromClass: Media::class, identifiers: ['id']),
    ])
])]
class Media
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'media')]
    private ?User $author = null;

    #[ORM\Column(length: 255)]
    private ?string $originalName = null;

    #[ORM\Column(length: 255)]
    private ?string $mimeType = null;

    #[ORM\Column]
    private ?int $size = null;

    #[ORM\Column(length: 255)]
    private ?string $path = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'media')]
    private ?Publication $publication = null;

    #[ORM\ManyToOne(inversedBy: 'media')]
    private ?Comment $comment = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'avatar')]
    private Collection $users;

    #[ORM\ManyToOne(inversedBy: 'media')]
    private ?Workspace $workspace = null;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): static
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

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

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setAvatar($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getAvatar() === $this) {
                $user->setAvatar(null);
            }
        }

        return $this;
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
}
