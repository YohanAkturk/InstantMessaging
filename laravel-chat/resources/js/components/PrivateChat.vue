<template>
  <div class="parent">
    <div class="div1">
      <div id="menuBar">
        <button @click="setFriendList" class="menuButton">
            Friend list
        </button>
        <button @click="setFriendRequest" class="menuButton">
            Friend request
        </button>
        <button @click="setSearchFriend" class="menuButton">
            Search friend
        </button>
      </div>
      <div v-if="friendList">
        <ul id="friendList" class='list'>
          <li
            v-for="friend in this.friends"
            :key="friend.fakeId"
            @click="setCurrentFriend(friend.fakeId)"
            :class="friend.online ? 'online' : 'offline'"
          >
            <div>
                {{ friend.name }}
                <button @click.stop="deleteFriend(friend.fakeId)">DELETE</button>
            </div>
          </li>
        </ul>
      </div>
      <div v-else-if="friendRequest">
        <ul id="requestList" class='list'>
          <li
            v-for="user in this.requests"
            :key="user.id"
          >
            <div>
                {{ user.name }}
                <button @click="acceptRequest(user.id)">ACCEPT</button>
                <button @click="refuseRequest(user.id)">REFUSE</button>
            </div>
          </li>
        </ul>
      </div>
      <div v-else-if="searchFriend" >
        <ul id="searchList" class='list'>
          <li
            v-for="user in this.users"
            :key="user.id"
            @click="sendRequestTo(user.id)"
          >
            <div>
                {{ user.name }}
            </div>
          </li>
        </ul>
      </div>
    </div>
    <div class="div2" v-if="this.currentFriend != null">
      <div id="chat">
        <ul class="chat">
          <li
            class="left clearfix"
            v-for="message in this.messages"
            :key="message.id"
          >
            <div class="clearfix">
              <div class="header">
                <strong>
                  {{ message.name }}
                </strong>
              </div>
              <p>
                {{ message.message }}
              </p>
            </div>
          </li>
        </ul>
      </div>
      <div id="textInput">
        <input
          id="btn-input"
          type="text"
          name="message"
          class="form-control input-sm"
          placeholder="Type your message here..."
          v-model="newMessage"
          @keyup.enter="sendMessage"
        />
        <span class="input-group-btn">
          <button
            class="btn btn-primary btn-sm"
            id="btn-chat"
            @click="sendMessage"
          >
            Send
          </button>
        </span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: ["user", "f", "u", "r"],

  data() {
    return {
      newMessage: "",
      messages: [],
      friends: [],
      onlineUsers: [],
      users: [],
      requests: [],
      currentFriend: null,
      friendList: true,
      friendRequest: false,
      searchFriend: false,
    };
  },

  created() {
    /**
     * Channel that detect when a user is connected or leaved the application.
     * When the page is created, get all the user that are connected
     */
    Echo.join(`activeUser`)
      .here((users) => {
        for (let user of users) {
          this.onlineUsers.push(user.id);
        }
        this.getUsers();
        this.getFriends();
        this.getFriendRequests();
      })
      .joining((user) => {
        for (let friend of this.friends) {
          if (friend.id == user.id) {
            friend.online = true;
            break;
          }
        }
      })
      .leaving((user) => {
        for (let friend of this.friends) {
          if (friend.id == user.id) {
            friend.online = false;
            break;
          }
        }
      });

    /**
     * Subscribe to the private channel of the user.
     * 
     * This channel will get all the messages that are sent to this user.
     */
    window.Echo.private("encrypted-privatechat." + this.user.id).listen(
      "MessageSent",
      (e) => {
        let cur = Date.now()/1000;
        if(Math.abs(cur - e.timestamp) < 60) {
          for(let friend of this.friends) {
            if(friend.id == e.sender_id) {
              this.messages.push(e.message);
            }
          }
        }
      }
    );

    /**
     * Subscribe to the private channel of the user.
     * 
     * This channel will get all the requests that has received to this user.
     */
    window.Echo.private("encrypted-request." + this.user.id).listen(
      "RequestSend",
      (e) => {
        let cur = Date.now()/1000;
        if(Math.abs(cur - e.timestamp) < 60) {
          let user = {name: e.user[0].name, id: e.user[0].id};
          this.requests.push(user);
        }
      }
    )
    
    /**
     * Subscribe to the private channel of the user.
     * 
     * This channel will receive the user that has accepted his friend request.
     */
    window.Echo.private("encrypted-requestAccept." + this.user.id).listen(
      "RequestAccept",
      (e) => {
        let cur = Date.now()/1000;
        if(Math.abs(cur - e.timestamp) < 60) {
          let id = e.userId;
          for(let request of this.requests) {
            if(request.id == id) {
              this.requests.splice(this.requests.indexOf(request), 1);
              break;
            }
          }

          for(let user of this.users) {
            if(user.id == id) {
              this.users.splice(this.users.indexOf(user), 1);
              let friend = {id: e.userId, name: e.name, fakeId: e.fakeId};
              this.friends.push(friend);
              break;
            }
          }
        }
      }
    )

    /**
     * Subscribe to the private channel of the user.
     * 
     * This channel will receive the user that has delete the current user from his friend list.
     */
    window.Echo.private("encrypted-deleteFriend." + this.user.id).listen(
      "FriendDelete",
      (e) => {
        let cur = Date.now()/1000;
        if(Math.abs(cur - e.timestamp) < 60) {
          let id = e.user.id;
          for(let friend of this.friends) {
            if(friend.id = id) {
              let tmp = friend;
              this.friends.splice(this.friends.indexOf(friend), 1);
              this.users.push(tmp);
              break;
            }
          }

          if(this.currentFriend == id) {
            this.currentFriend = null;
          }
        }
      }
    )
  },

  watch: {
    /**
     * When the current friend change, get the messages of this friend.
     */
    currentFriend(val) {
      this.fetchMessages();
    },
  },

  methods: {
    /**
     * Get the messages of the current friend.
     */
    fetchMessages() {

      const config = {
        headers:{
          timestamp: (Date.now()/1000),
        }
      };

      //GET request to the messages route in our Laravel server to fetch all the messages
      axios.get("/chat/messages/" + this.currentFriend, config).then((response) => {
        //Save the response in the messages array to display on the chat view
        let cur = Date.now()/1000;
        if(Math.abs(cur - response.data.timestamp) < 60) {
          if(response.data.messages.length != 0) {
            this.messages = response.data.messages;
          }else {
            this.messages = [];
          }
        }
      });
    },

    /**
     * Send a message to the current friend.
     */
    sendMessage() {
      if (!this.newMessage) {
        return alert("Please enter a message");
      }

      if (!this.currentFriend) {
        return alert("Please select a friend");
      }

      const config = {
        headers:{
          timestamp: (Date.now()/1000),
        }
      };

      //POST request to the messages route with the message data in order for our Laravel server to broadcast it.
      axios.post("/chat/messages", {
          message: this.newMessage,
          friend: this.currentFriend
        }, config)
        .then((response) => {
          let cur = Date.now()/1000;
          if(Math.abs(cur - response.data.timestamp) < 60) {
            if(response.data.status == 200) {
              return alert("The message content is too big");
            }else if(response.data.status == 100){
              this.newMessage = "";
              this.messages.push(response.data.message);
            }
          }
        });
    },

    /**
     * The all the friends of the current user and if they are online or not.
     */
    getFriends() {
      this.f.forEach(element => {
            let isFriendOnline = this.onlineUsers.indexOf(element.id);
            if (isFriendOnline != -1) {
              element["online"] = true;
            } else {
              element["online"] = false;
            }
            this.friends.push(element);
        });
    },

    /**
     * Gets the user and check if they are online.
     */
    getUsers() {
      this.u.forEach(element => {
            let isOnline = this.onlineUsers.indexOf(element.id);
            if (isOnline != -1) {
              element["online"] = true;
            } else {
              element["online"] = false;
            }
            this.users.push(element);
        });
    },

    /**
     * Get the friend requests.
     */
    getFriendRequests() {
      this.requests = this.r;
    },

    /**
     * Send a friend request to a user.
     */
    sendRequestTo(id) {

      const config = {
        headers:{
          timestamp: (Date.now()/1000),
        }
      };

      axios.post("/chat/request", {user:id}, config).then(response => {
        let cur = Date.now()/1000;
        if(Math.abs(cur - response.data.timestamp) < 60) {
          if(response.data.status == 200) {
            return alert("Error when sending the friend request");
          }else if(response.data.status == 100){
            return alert("Friend request send");
          }
        }
      });
    },

    /**
     * Change the current friend of the user.
     */
    setCurrentFriend(friendId) {
      this.currentFriend = friendId;
    },

    /**
     * Change the current part on the left of the page to display the friend list.
     */
    setFriendList() {
        this.friendList = true;
        this.friendRequest = false;
        this.searchFriend = false;
    },

    /**
     * Change the current part on the left of the page to display the friend requests.
     */
    setFriendRequest() {
        this.friendList = false;
        this.friendRequest = true;
        this.searchFriend = false;
    },

    /**
     * Change the current part on the left of the page to display all the user that are not friend
     * with the current user.
     */
    setSearchFriend() {
        this.friendList = false;
        this.friendRequest = false;
        this.searchFriend = true;
    },

    /**
     * Accept the request that has been send by a user.
     */
    acceptRequest(id) {
      for(let request of this.requests) {
        if(request.id == id) {
          this.requests.splice(this.requests.indexOf(request), 1);
          break;
        }
      }

      for(let user of this.users) {
        if(user.id == id) {
          this.users.splice(this.users.indexOf(user), 1);
          break;
        }
      }

      const config = {
        headers:{
          timestamp: (Date.now()/1000),
        }
      };
      
      axios.post("/chat/request/accept", {user:id}, config).then(response => {
        let cur = Date.now()/1000;
        if(Math.abs(cur - response.data.timestamp) < 60) {
          if(response.data.status == 200) {
            return alert("Error when accepting request.");
          }else if(response.data.status == 100){
            this.friends.push(response.data.friend);
          }
        }
      });
    },

    /**
     * Refuse a request that has been send by a user.
     */
    refuseRequest(id) {
      for(let request of this.requests) {
        if(request.id == id) {
          this.requests.splice(this.requests.indexOf(request), 1);
          break;
        }
      }

      const config = {
        headers:{
          timestamp: (Date.now()/1000),
        }
      };

      axios.post("/chat/request/refuse", {user:id}, config).then(response => {
        let cur = Date.now()/1000;
        if(Math.abs(cur - response.data.timestamp) < 60) {
          if(response.data.status == 200) {
            return alert("Error when refusing request.");
          }else if(response.data.status == 100){
            return alert("Friend request refuse.");
          }
        }
      });
    },

    /**
     * Delete a friend from the friend list.
     */
    deleteFriend(id) {
      for(let friend of this.friends) {
        if(id == friend.fakeId) {
          let tmp = friend;
          this.friends.splice(this.friends.indexOf(friend), 1);
          this.users.push(tmp);
          break;
        }
      }

      if(this.currentFriend == id) {
        this.currentFriend = null;
      }

      const config = {
        headers:{
          timestamp: (Date.now()/1000),
        }
      };

      axios.post("/chat/friends", {user:id}, config).then(response => {
        let cur = Date.now()/1000;
        if(Math.abs(cur - response.data.timestamp) < 60) {
          if(response.data.status == 200) {
            return alert("Error when deleting friend");
          }else if(response.data.status == 100){
            return alert("Friend delete.");
          }
        }
      });
    }
  },
};
</script>
