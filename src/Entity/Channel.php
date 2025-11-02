<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\ChannelRepository;
use App\State\ChannelProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ChannelRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['channel:read']],
    denormalizationContext: ['groups' => ['channel:write']],
    operations: [
    new GetCollection(
        uriTemplate: '/{slug}/channels',
        uriVariables: [
            'slug' => new Link(
                fromClass: Workspace::class,
                identifiers: ['slug'],
                fromProperty: 'channels'
            ),
        ],
        security: "is_granted('ROLE_USER')"
    ),
    new Get(
        uriTemplate: '/{slug}/channels/{id}',
        uriVariables: [
            'slug' => new Link(
                fromClass: Workspace::class,
                identifiers: ['slug'],
                fromProperty: 'channels'
            ),
            'id'   => new Link(fromClass: Channel::class, identifiers: ['id']),
        ],
        security: "object.getWorkspace() === service('App\\Context\\CurrentWorkspace').get()"
    ),
    new Post(
        uriTemplate: '/{slug}/channels',
        uriVariables: [
            'slug' => new Link(
                fromClass: Workspace::class,
                identifiers: ['slug'],
                fromProperty: 'channels'
            ),
        ],
        read: false,
        processor: ChannelProcessor::class,
        securityPostDenormalize: "is_granted('ROLE_USER')"
    ),
    new Patch(
        uriTemplate: '/{slug}/channels/{id}',
        uriVariables: [
            'slug' => new Link(
                fromClass: Workspace::class,
                identifiers: ['slug'],
                fromProperty: 'channels'
            ),
            'id'   => new Link(fromClass: Channel::class, identifiers: ['id']),
        ],
        processor: ChannelProcessor::class,
        securityPostDenormalize: "is_granted('ROLE_USER')",
    ),
    new Delete(
        uriTemplate: '/{slug}/channels/{id}',
        uriVariables: [
            'slug' => new Link(
                fromClass: Workspace::class,
                identifiers: ['slug'],
                fromProperty: 'channels'
            ),
            'id'   => new Link(fromClass: Channel::class, identifiers: ['id']),
        ],
        security: "is_granted('ROLE_USER')"
    ),
])]
class Channel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['channel:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'channels')]
    #[Groups(['channel:read'])]
    private ?Workspace $workspace = null;

    #[ORM\Column(length: 255)]
    #[Groups(['channel:read', 'channel:write'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['channel:read'])]
    private ?string $slug = null;

    /**
     * @var Collection<int, Publication>
     */
    #[ORM\OneToMany(targetEntity: Publication::class, mappedBy: 'channel')]
    #[Groups(['channel:read'])]
    private Collection $publications;

    public function __construct()
    {
        $this->publications = new ArrayCollection();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return Collection<int, Publication>
     */
    public function getPublications(): Collection
    {
        return $this->publications;
    }

    public function addPublication(Publication $publication): static
    {
        if (!$this->publications->contains($publication)) {
            $this->publications->add($publication);
            $publication->setChannel($this);
        }

        return $this;
    }

    public function removePublication(Publication $publication): static
    {
        if ($this->publications->removeElement($publication)) {
            // set the owning side to null (unless already changed)
            if ($publication->getChannel() === $this) {
                $publication->setChannel(null);
            }
        }

        return $this;
    }
}
