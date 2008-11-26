/**
 * @requires Xquared.js
 */
xq.Timer = xq.Class(/** @lends xq.Timer.prototype */{
	/**
     * @constructs
     * 
     * @param {Number} precision precision in milliseconds
	 */
	initialize: function(precision) {
		xq.addToFinalizeQueue(this);
	
		this.precision = precision;
		this.jobs = {};
		this.nextJobId = 0;
		
		this.checker = null;
	},
	
	finalize: function() {
		this.stop();
	},
	
	/**
	 * starts timer
	 */
	start: function() {
		this.stop();
		
		this.checker = window.setInterval(function() {
			this.executeJobs();
		}.bind(this), this.precision);
	},
	
	/**
	 * stops timer
	 */
	stop: function() {
		if(this.checker) window.clearInterval(this.checker);
	},
	
	/**
	 * registers new job
	 * 
	 * @param {Function} job function to execute
	 * @param {Number} interval interval in milliseconds
	 * 
	 * @return {Number} job id
	 */
	register: function(job, interval) {
		var jobId = this.nextJobId++;
		
		this.jobs[jobId] = {
			func:job,
			interval: interval,
			lastExecution: Date.get()
		};
		
		return jobId;
	},
	
	/**
	 * unregister job by job id
	 * 
	 * @param {Number} job id
	 */
	unregister: function(jobId) {
		delete this.jobs[jobId];
	},
	
	/**
	 * Execute all expired jobs immedialty. This method will be called automatically by interval timer.
	 */
	executeJobs: function() {
		var curDate = new Date();
		
		for(var id in this.jobs) {
			var job = this.jobs[id];
			if(job.lastExecution.elapsed(job.interval, curDate)) {
				try {
					job.lastReturn = job.func();
				} catch(e) {
					job.lastException = e;
				} finally {
					job.lastExecution = curDate;
				}
			}
		}
	}
});