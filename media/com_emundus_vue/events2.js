import { J as FetchClient } from "./app_emundus.js";
const fetchClient = new FetchClient("events");
const eventsService = {
  async getEvents() {
    try {
      return await fetchClient.get("getevents");
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  async getEventsNames() {
    try {
      return await fetchClient.get("geteventsnames");
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  // Getters
  async getLocations() {
    try {
      return await fetchClient.get("getlocations");
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  async getLocation(location_id) {
    try {
      return await fetchClient.get("getlocation", { location_id });
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  async getRooms(location_id = 0) {
    try {
      return await fetchClient.get("getrooms", { location_id });
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  async getSpecifications() {
    try {
      return await fetchClient.get("getspecifications");
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  async getEvent(event_id) {
    try {
      return await fetchClient.get("getevent", { event_id });
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  async getEventsSlots(start, end, eventsIds = "") {
    try {
      return await fetchClient.get("geteventsslots", {
        start,
        end,
        events_ids: eventsIds
      });
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  async getEventsAvailabilities(start, end, eventsIds = "") {
    try {
      return await fetchClient.get("geteventsavailabilities", {
        start,
        end,
        events_ids: eventsIds
      });
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  // Post requests
  async saveLocation(data) {
    if (data.rooms) {
      data.rooms = JSON.stringify(data.rooms);
    }
    try {
      return await fetchClient.post("savelocation", data);
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  async createEvent(data) {
    if (data.campaigns) {
      data.campaigns = JSON.stringify(data.campaigns);
    }
    if (data.programs) {
      data.programs = JSON.stringify(data.programs);
    }
    try {
      return await fetchClient.post("createevent", data);
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  async saveEventSlot(data) {
    try {
      return await fetchClient.post("saveeventslot", data);
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  async deleteEventSlot(id) {
    try {
      return await fetchClient.delete("deleteeventslot", { id });
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  async setupSlot(data) {
    try {
      return await fetchClient.post("setupslot", data);
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  async saveBookingNotifications(data) {
    try {
      return await fetchClient.post("savebookingnotifications", data);
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  async editEvent(data) {
    if (data.campaigns) {
      data.campaigns = JSON.stringify(data.campaigns);
    }
    if (data.programs) {
      data.programs = JSON.stringify(data.programs);
    }
    try {
      return await fetchClient.post("editevent", data);
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  async editSlot(data) {
    try {
      return await fetchClient.post("editslot", data);
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  async getAvailabilitiesByCampaignsAndPrograms(start = "", end = "", location = 0, check_booking_limit_reached = 0, events_ids = []) {
    try {
      return await fetchClient.get("getavailabilitiesbycampaignsandprograms", {
        start,
        end,
        location,
        check_booking_limit_reached,
        events_ids
      });
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  async getMyBookings() {
    try {
      return await fetchClient.get("getmybookings");
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  async getApplicantBookings() {
    try {
      return await fetchClient.get("getapplicantbookings");
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  async deleteBooking(booking_id) {
    try {
      return await fetchClient.get("deletebooking", { booking_id });
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  async getAvailabilityRegistrants(availability = 0) {
    try {
      return await fetchClient.get("getavailabilityregistrants", {
        availability
      });
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  async assocUsers(slots, users, replace = true) {
    console.log(slots);
    console.log(users);
    try {
      return await fetchClient.post("assocusers", { slots, users, replace });
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  }
};
export {
  eventsService as e
};
