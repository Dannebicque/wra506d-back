<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use App\Repository\WorkspaceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkspaceRepository::class)]
class Workspace
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $slug = null;

    /**
     * @var Collection<int, Channel>
     */
    #[ORM\OneToMany(targetEntity: Channel::class, mappedBy: 'workspace')]
    private Collection $channels;

    /**
     * @var Collection<int, Publication>
     */
    #[ORM\OneToMany(targetEntity: Publication::class, mappedBy: 'workspace')]
    private Collection $publications;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'workspace')]
    private Collection $comments;

    /**
     * @var Collection<int, Reaction>
     */
    #[ORM\OneToMany(targetEntity: Reaction::class, mappedBy: 'workspace')]
    private Collection $reactions;

    #[ORM\Column(options: ['default' => false])] private bool $allowSelfSignup = false;
    #[ORM\Column(nullable: true)] private ?string $joinCodeHash = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'workspace')]
    private Collection $users;

    public function __construct()
    {
        $this->channels = new ArrayCollection();
        $this->publications = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->reactions = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
     * @return Collection<int, Channel>
     */
    public function getChannels(): Collection
    {
        return $this->channels;
    }

    public function addChannel(Channel $channel): static
    {
        if (!$this->channels->contains($channel)) {
            $this->channels->add($channel);
            $channel->setWorkspace($this);
        }

        return $this;
    }

    public function removeChannel(Channel $channel): static
    {
        if ($this->channels->removeElement($channel)) {
            // set the owning side to null (unless already changed)
            if ($channel->getWorkspace() === $this) {
                $channel->setWorkspace(null);
            }
        }

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
            $publication->setWorkspace($this);
        }

        return $this;
    }

    public function removePublication(Publication $publication): static
    {
        if ($this->publications->removeElement($publication)) {
            // set the owning side to null (unless already changed)
            if ($publication->getWorkspace() === $this) {
                $publication->setWorkspace(null);
            }
        }

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
            $comment->setWorkspace($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getWorkspace() === $this) {
                $comment->setWorkspace(null);
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
            $reaction->setWorkspace($this);
        }

        return $this;
    }

    public function removeReaction(Reaction $reaction): static
    {
        if ($this->reactions->removeElement($reaction)) {
            // set the owning side to null (unless already changed)
            if ($reaction->getWorkspace() === $this) {
                $reaction->setWorkspace(null);
            }
        }

        return $this;
    }

    public function isAllowSelfSignup(): bool
    {
        return $this->allowSelfSignup;
    }

    public function setAllowSelfSignup(bool $allowSelfSignup): void
    {
        $this->allowSelfSignup = $allowSelfSignup;
    }

    public function getJoinCodeHash(): ?string
    {
        return $this->joinCodeHash;
    }

    public function setJoinCodeHash(?string $joinCodeHash): void
    {
        $this->joinCodeHash = $joinCodeHash;
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
            $user->setWorkspace($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getWorkspace() === $this) {
                $user->setWorkspace(null);
            }
        }

        return $this;
    }


}
